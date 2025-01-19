<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

use App\Models\LessonModel;

class ExerciseModel extends Model
{
    protected $table      = 'exercises';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $emptyReward = [
        'experience' => 10
    ];
    protected $allowedFields = [
        'lesson_id',
        'user_id',
        'exercise_pending',
        'exercise_submitted',
        'points',
        'attempts',
        'began_at',
        'finished_at'
    ];
    private $empty_data = [
        'current_page'      => 0,
        'answers'           => [],
        'total_pages'       => 0,
        'actions'           => [
            'main'          => 'next',
        ],
        'totals'            => [
            'total'         => 0,
            'points'        => 0,
            'difference'    => 0,
            'reward'        => []
        ]
    ];
    protected $correctnessGradation = [
        '0' => [0, 40],
        '1' => [40, 80],
        '2' => [80, 99],
        '3' => [99, 100]
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $beforeInsert = ['jsonPrepare'];
    protected $beforeUpdate = ['jsonPrepare'];

    public function getItem ($exercise_id, $mode = 'default') 
    {
        $ResourceModel = model('ResourceModel');
        $exercise = $this->select('exercises.*, COALESCE(exercises.exercise_pending, exercises.exercise_submitted) as data')
        ->where('id', $exercise_id)->where('exercises.user_id', session()->get('user_id'))->get()->getRowArray();
        if(!empty($exercise)){
            $exercise['data'] = json_decode($exercise['data'], true, JSON_UNESCAPED_UNICODE);
            if(!empty($exercise['data']['totals']['reward'])){
                $exercise['data']['totals']['reward'] = $ResourceModel->proccessItemReward($exercise['data']['totals']['reward']);
            }
            unset($exercise['exercise_pending']);
            unset($exercise['exercise_submitted']);
        }
        return $exercise;
    }
    public function getItemByLesson ($lesson_id) 
    {
        $exercise = $this->select('exercises.*, COALESCE(exercises.exercise_pending, exercises.exercise_submitted) as data')
        ->where('lesson_id', $lesson_id)->where('exercises.user_id', session()->get('user_id'))->get()->getRowArray();
        if(!empty($exercise)){
            $exercise['data'] = json_decode($exercise['data'], true, JSON_UNESCAPED_UNICODE);
            unset($exercise['exercise_pending']);
            unset($exercise['exercise_submitted']);
        }
        return $exercise;
    }
    public function getItemData ($exercise_id) 
    {
        $exercise = $this->select('COALESCE(exercises.exercise_pending, exercises.exercise_submitted) as data')
        ->where('id', $exercise_id)->get()->getRowArray();
        if(!empty($exercise)){
            $exercise['data'] = json_decode($exercise['data'], true, JSON_UNESCAPED_UNICODE);
            return $exercise;
        }
        return null;
    }
    public function createItem ($lesson_id)
    {
        $LessonModel = new LessonModel();
        $lesson = $LessonModel->where('lessons.id', $lesson_id)->get()->getRowArray();
        if (empty($lesson)) {
            return 'not_found';
        }
        $cost_config = json_decode($lesson['cost_config'], true);

        $ResourceModel = model('ResourceModel');
        if(!$ResourceModel->enrollUserList(session()->get('user_id'), $cost_config, 'substract')){
            //return 'not_enough_resources';
        } 
        
        $pages = json_decode($lesson['pages'], true);
        $this->empty_data['total_pages'] = count($pages);
        $this->empty_data['totals']['total'] = $this->calculateTotalPoints($pages);


        $this->transBegin();
        $data = [
            'lesson_id' => $lesson_id,
            'user_id' => session()->get('user_id'),
            'exercise_pending' => $this->empty_data,
            'exercise_submitted' => NULL,
            'began_at' => date("Y-m-d H:i:s")
        ];
        $exercise_id = $this->insert($data, true);
        $this->transCommit();

        return $exercise_id;        
    }
    public function updateItem ($data, $action = false)
    {
        if(!empty($action)){
            if($action == 'start'){
                $data['exercise_pending']   = $data['data'];
                $data['began_at']           = date("Y-m-d H:i:s");
                $data['finished_at']        = NULL;
            } 
            if($action == 'finish'){
                $data['exercise_pending']   = NULL;
                $data['finished_at']        = date("Y-m-d H:i:s");
                $data['exercise_submitted'] = $data['data'];
                $data['points']             = $data['data']['totals']['points'];
                unset($data['exercise_submitted']['answers']);
            }
        } else {
            $data['exercise_pending']   = $data['data'];
            $data['finished_at']        = NULL;
        }
        $this->transBegin();
        $this->set($data);
        $this->where('id', $data['id']);
        $result = $this->update();
        $this->transCommit();
        return $result;        
    }
    public function redoItem($lesson_id)
    {
        $exercise_old = $this->select('exercises.id, JSON_UNQUOTE(JSON_EXTRACT(exercises.exercise_submitted, "$.total_pages")) as total_pages, exercises.attempts, JSON_UNQUOTE(JSON_EXTRACT(exercises.exercise_submitted, "$.totals.total")) as total_points')
        ->where('lesson_id', $lesson_id)->where('exercises.user_id = '.session()->get('user_id'))->get()->getRowArray();
        $exercise = [];
        $exercise['id'] = $exercise_old['id'];
        $exercise['data'] = $this->empty_data;
        $exercise['data']['totals']['total'] = (int) $exercise_old['total_points'];
        $exercise['data']['total_pages'] = (int) $exercise_old['total_pages'];
        $exercise['attempts'] = $exercise_old['attempts'] + 1;
        return $this->updateItem($exercise, 'start');
    }
    public function getTotal($data, $mode = 'sum')
    {
        $this->where('exercises.user_id = '.session()->get('user_id'));
        if($mode == 'sum')      $this->select("COALESCE(sum(exercises.points), 0) as total");
        if($mode == 'count')    $this->select("COALESCE(count(exercises.points), 0) as total");
        if($data['date_start']) $this->where("exercises.finished_at > '".$data['date_start']."'");
        if($data['date_end'])   $this->where("exercises.finished_at < '".$data['date_end']."'");
        if($data['lesson_id'])  $this->where("exercises.lesson_id = '".$data['lesson_id']."'");
        $exercise = $this->get()->getRowArray();
        return $exercise['total'];
    }
    public function calculateTotalPoints($pages)
    {
        $ExerciseAnswerModel = model('ExerciseAnswerModel');
        $points_config = $ExerciseAnswerModel->points_config;
        $result = 0;
        foreach($pages as $page){
            $page_total_points = $points_config['none'];
            if(!empty($page['template_config']['input_list'])){
                $page_total_points += count($page['template_config']['input_list'])*$points_config[$page['form_template']];
            }
            $result += $page_total_points;
        }
        $result -= $points_config['none'];
        return $result;
    }
    public function calculateItemDifference ($exercise)
    {
        $exercise_submitted = json_decode($this->where('id',$exercise['id'])->get()->getRowArray()['exercise_submitted'] ?? '[]', true, JSON_UNESCAPED_UNICODE); 
        if(!empty($exercise_submitted)) return $exercise['data']['totals']['points'] - $exercise_submitted['totals']['points'];
        return $exercise['data']['totals']['points'];
    }
    public function calculateItemReward($lesson_id, $totals)
    {
        $LessonModel = model('LessonModel');
        $prev_reward_level = $this->calculateRewardLevel($totals['points']-$totals['difference'], $totals['total']);
        $reward_config = json_decode($LessonModel->find($lesson_id)['reward_config'] ?? '[]', true);
        $level_diff = $totals['reward_level'] - $prev_reward_level;
        if($level_diff <= 0){
            return $this->emptyReward;
        } else {
            $reward = $reward_config[$totals['reward_level']];
            if(isset($reward_config[$prev_reward_level])){
                $reward_old = $reward_config[$prev_reward_level];
                foreach($reward as $resource => &$quantity){
                    $quantity -= $reward_old[$resource] ?? 0;
                }
            }
            return $reward;
        }
    }
    public function calculateRewardLevel($points, $total)
    {
        $progress = $points / $total * 100;
        foreach($this->correctnessGradation as $level => $range){
            if(($range[0] <= $progress) && ($progress <= $range[1])) return $level;
        }
        return 0;
    }


    protected function jsonPrepare (array $data)
    {
        if ( isset($data['data']['exercise_pending']) ){
            $data['data']['exercise_pending'] = json_encode($data['data']['exercise_pending'], JSON_UNESCAPED_UNICODE);
        }
        if ( isset($data['data']['exercise_submitted']) ){
            $data['data']['exercise_submitted'] = json_encode($data['data']['exercise_submitted'], JSON_UNESCAPED_UNICODE);
        }
        return $data;
    }


}