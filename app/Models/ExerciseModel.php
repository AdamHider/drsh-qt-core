<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\LessonModel;
use CodeIgniter\Events\Events;

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
        'pages',
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
        '0' => [0, 39],
        '1' => [40, 79],
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
            unset($exercise['pages']);
        }
        return $exercise;
    }
    public function getItemByLesson ($lesson_id) 
    {
        $exercise = $this->select('exercises.*, COALESCE(exercises.exercise_pending, exercises.exercise_submitted) as data')
        ->where('lesson_id', $lesson_id)->where('exercises.user_id', session()->get('user_id'))->get()->getRowArray();
        if(!empty($exercise)){
            $exercise['data'] = json_decode($exercise['data'], true, JSON_UNESCAPED_UNICODE);
            $exercise['pages'] = json_decode($exercise['pages'], true, JSON_UNESCAPED_UNICODE);
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
        $LessonGeneratorModel = new LessonGeneratorModel();
        $lesson = $LessonModel->where('lessons.id', $lesson_id)->get()->getRowArray();
        if (empty($lesson)) {
            return 'not_found';
        }
        $cost_config = json_decode($lesson['cost_config'], true);

        $ResourceModel = model('ResourceModel');
        if(!$ResourceModel->enrollUserList(session()->get('user_id'), $cost_config, 'substract')){
            //return 'not_enough_resources';
        } 
        
        $pages = $LessonGeneratorModel->generateList($lesson_id);
        
        $this->empty_data['total_pages'] = count($pages);
        $this->empty_data['totals']['total'] = $this->calculateTotalPoints($pages);

        $this->transBegin();
        $data = [
            'lesson_id'             => $lesson_id,
            'user_id'               => session()->get('user_id'),
            'pages'                 => $pages,
            'exercise_pending'      => $this->empty_data,
            'exercise_submitted'    => NULL,
            'began_at'              => date("Y-m-d H:i:s")
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
                $data['exercise_submitted'] = $this->chooseBestResult($data);
                $data['points']             = $data['exercise_submitted']['totals']['points'];
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
        if($result && $action == 'finish') {
            $LessonUnblockUsermapModel = model('LessonUnblockUsermapModel');
            if($data['exercise_submitted']['totals']['reward_level'] > 0){
                $LessonUnblockUsermapModel->unblockNext('lessons', $data['lesson_id']);
                Events::trigger('lessonFinished', $data['lesson_id']);
            }
            $ResourceModel = model('ResourceModel');
            $ResourceModel->enrollUserList(session()->get('user_id'), $data['exercise_submitted']['totals']['reward']);
        }
        return $result;        
    }
    public function redoItem($lesson_id)
    {
        $LessonGeneratorModel = new LessonGeneratorModel();
        $exercise_old = $this->select('exercises.id, exercises.lesson_id, JSON_UNQUOTE(JSON_EXTRACT(exercises.exercise_submitted, "$.total_pages")) as total_pages, exercises.attempts, JSON_UNQUOTE(JSON_EXTRACT(exercises.exercise_submitted, "$.totals")) as totals')
        ->where('lesson_id', $lesson_id)->where('exercises.user_id = '.session()->get('user_id'))->get()->getRowArray();
        $exercise = [];
        $exercise_old['totals'] = json_decode($exercise_old['totals'], true);
        $exercise['id'] = $exercise_old['id'];
        $exercise['pages'] = $LessonGeneratorModel->generateList($exercise_old['lesson_id']);
        if(empty($exercise['pages'])){
            return false;
        }
        $exercise['data'] = $this->empty_data;
        $exercise['data']['total_pages'] = count($exercise['pages']);
        $exercise['data']['totals']['total'] = $this->calculateTotalPoints($exercise['pages']);
        $exercise['data']['totals']['prev_points'] = (int) $exercise_old['totals']['points'];
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
                $page_total_points += $points_config[$page['form_template']];
            }
            $result += $page_total_points;
        }
        $result -= $points_config['none'];
        return $result;
    }
    public function chooseBestResult ($exercise)
    {
        $exercise_submitted = json_decode($this->where('id',$exercise['id'])->get()->getRowArray()['exercise_submitted'] ?? '[]', true, JSON_UNESCAPED_UNICODE); 
        $difference = $exercise['data']['totals']['points'];
        if(!empty($exercise_submitted)) {
            $difference = $exercise['data']['totals']['points'] - $exercise_submitted['totals']['points'];
        }
        if($difference <= 0){
            $exercise['data'] = $exercise_submitted;
            $exercise['data']['totals']['reward_level'] = 0;
        } else {
            $exercise['data']['totals']['reward_level'] = $this->calculateRewardLevel($exercise['data']['totals']['points'], $exercise['data']['totals']['total']);
        }
        $exercise['data']['totals']['is_maximum'] = $exercise['data']['totals']['points']-$difference == $exercise['data']['totals']['total'];
        $exercise['data']['totals']['difference'] = $difference;
        $exercise['data']['totals']['reward'] = $this->calculateItemReward($exercise['lesson_id'], $exercise['data']['totals']);
       
        return $exercise['data'];
    }
    public function calculateItemReward($lesson_id, $totals)
    {
        if($totals['reward_level'] == 0){
            return $this->emptyReward;
        } 
        $LessonModel = model('LessonModel');
        $prev_reward_level = $this->calculateRewardLevel($totals['points']-$totals['difference'], $totals['total']);
        if($prev_reward_level == $totals['reward_level']){
            return $this->emptyReward;
        }
        $reward_config = json_decode($LessonModel->find($lesson_id)['reward_config'] ?? '[]', true);
        $reward = $reward_config[$totals['reward_level']];
        if(isset($reward_config[$prev_reward_level])){
            $reward_old = $reward_config[$prev_reward_level];
            foreach($reward as $resource => &$quantity){
                $quantity -= $reward_old[$resource] ?? 0;
                if($quantity < 0) $quantity = 0;
            }
        }
        return $reward;
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
        if ( isset($data['data']['pages']) ){
            $data['data']['pages'] = json_encode(array_values($data['data']['pages']), JSON_UNESCAPED_UNICODE);
        }
        if ( isset($data['data']['exercise_pending']) ){
            $data['data']['exercise_pending'] = json_encode($data['data']['exercise_pending'], JSON_UNESCAPED_UNICODE);
        }
        if ( isset($data['data']['exercise_submitted']) ){
            $data['data']['exercise_submitted'] = json_encode($data['data']['exercise_submitted'], JSON_UNESCAPED_UNICODE);
        }
        return $data;
    }


}