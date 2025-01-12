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
            'main'          => 'confirm',
            'back_attempts' => 3
        ],
        'totals'            => [
            'total'         => 0
        ]
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $beforeInsert = ['jsonPrepare'];
    protected $beforeUpdate = ['jsonPrepare'];

    public function getItem ($exercise_id, $mode = 'default') 
    {
        $exercise = $this->select('exercises.*, COALESCE(exercises.exercise_pending, exercises.exercise_submitted) as data')
        ->where('id', $exercise_id)->where('exercises.user_id', session()->get('user_id'))->get()->getRowArray();
        if(!empty($exercise)){
            $exercise['data'] = json_decode($exercise['data'], true, JSON_UNESCAPED_UNICODE);
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
        $this->empty_data['total_pages'] = count(json_decode($lesson['pages'], true));

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
                $data['data']['totals']     = $this->calculateTotals($data);
                $data['exercise_submitted'] = $this->chooseBestResult($data);
                $data['points']             = $data['exercise_submitted']['totals']['total'];
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
        $exercise_old = $this->select('exercises.id, JSON_EXTRACT(exercises.exercise_submitted, "$.total_pages") as total_pages, exercises.attempts')
        ->where('lesson_id', $lesson_id)->where('exercises.user_id = '.session()->get('user_id'))->get()->getRowArray();
        $exercise = [];
        $exercise['id'] = $exercise_old['id'];
        $exercise['data'] = $this->empty_data;
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
    private function calculateTotals($exercise)
    {
        $data = [];
        $data['total'] = $exercise['data']['totals']['total'];
        $data['exercises'] = $exercise['data']['totals']['total'];
        if(!empty($exercise['finished_at'])){
            $time_points = $this->calculateTotalTimePoints($exercise);
            $data['total'] += $time_points;
            $data['time'] = $time_points;
        } else {
            $data['time'] = 0;
        }
        if(!empty($exercise['attempts']) && $exercise['attempts'] !== 0){
            $attempts_points = $exercise['attempts']*10;
            if($attempts_points > 50){
                $attempts_points = 50;
            }
            $data['total'] += $attempts_points;
            $data['attempts'] = $attempts_points;
            $data['attempts_count'] = $exercise['attempts'];
        } else {
            $data['attempts'] = 0;
            $data['attempts_count'] = 1;
        }
        return $data;
    }
    private function calculateTotalTimePoints($exercise)
    {
        $difference = strtotime($exercise['finished_at']) - strtotime($exercise['began_at']);
        $time_points = 200 - ceil($difference/60*7);
        if($time_points < 0 || $difference < 0){
            $time_points = 10;
        }
        if($time_points > 200){
            $time_points = 200;
        }
        return $time_points;
    }

    private function chooseBestResult ($exercise)
    {
        $exercise_pending = $exercise['data'];
        $exercise_submitted = $this->select('exercise_submitted')->where('id', $exercise['id'])->get()->getRowArray()['exercise_submitted']; 
        $exercise_submitted = json_decode($exercise_submitted, true, JSON_UNESCAPED_UNICODE);
        if(!empty($exercise_submitted) && $exercise_submitted['totals']['total'] > $exercise_pending['totals']['total']){
            return $exercise_submitted;
        } else {
            return $exercise_pending;
        }
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