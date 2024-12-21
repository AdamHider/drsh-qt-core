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
        'lesson_pages',
        'exercise_pending',
        'exercise_submitted',
        'points',
        'attempts',
        'began_at',
        'finished_at'
    ];
    private $empty_data = [
        'back_attempts'     => 3,
        'again_attempts'    => 3,
        'current_page'      => 0,
        'answers'           => [],
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
        ->where('id', $exercise_id)->get()->getRowArray();
        if(!empty($exercise)){
            $exercise['lesson_pages'] = json_decode($exercise['lesson_pages'], true, JSON_UNESCAPED_UNICODE);
            $exercise['data'] = json_decode($exercise['data'], true, JSON_UNESCAPED_UNICODE);
    
            $exercise['data']['progress_percentage'] = floor($exercise['data']['current_page'] * 100 / count($exercise['lesson_pages']));
            unset($exercise['exercise_pending']);
            unset($exercise['exercise_submitted']);
            if($mode == 'lite'){
                unset($exercise['lesson_pages']);
            }
        }
        return $exercise;
    }
    public function getItemData ($exercise_id) 
    {
        $exercise = $this->select('COALESCE(exercises.exercise_pending, exercises.exercise_submitted) as data')
        ->where('id', $exercise_id)->get()->getRowArray();
        if(!empty($exercise)){
            $exercise['data'] = json_decode($exercise['data'], true, JSON_UNESCAPED_UNICODE);
            $exercise['data']['progress_percentage'] = floor($exercise['data']['current_page'] * 100 / count($exercise['lesson_pages']));
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
            return 'bad_request';
        } 

        $lesson['pages'] = (array) json_decode($lesson['pages'], true, JSON_UNESCAPED_UNICODE);
        $lesson_pages = $LessonModel->composePages($lesson['pages'], $lesson['type']);
        $this->transBegin();
        $data = [
            'lesson_id' => $lesson_id,
            'user_id' => session()->get('user_id'),
            'lesson_pages' => $lesson_pages,
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
        $exercise = $this->select('exercises.*, COALESCE(exercises.exercise_pending, exercises.exercise_submitted) as data')
        ->where('lesson_id', $lesson_id)->get()->getRowArray();
        if(!empty($exercise)){
            $exercise['lesson_pages'] = json_decode($exercise['lesson_pages'], true, JSON_UNESCAPED_UNICODE);
            $exercise['data'] = json_decode($exercise['data'], true, JSON_UNESCAPED_UNICODE);
            unset($exercise['exercise_pending']);
            unset($exercise['exercise_submitted']);
        } else {
            return false;
        }
        $exercise['data'] = $this->empty_data;
        $exercise['attempts']++;
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
        $data['total_pages'] = count($exercise['data']['answers']);
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
        $start_date = new \DateTime($exercise['began_at']);
        $finish_date = new \DateTime($exercise['finished_at']);
        //$difference_readable = $start_date->diff($finish_date)->format('%H:%I:%S');
        $difference = strtotime($exercise['finished_at']) - strtotime($exercise['began_at']);
        $more_than_day = $difference/3600 > 24;
        if($more_than_day){
            $time_difference = 'More than a day';
        }
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
        $exercise_submitted = $this->select('exercise_submitted')->where('id', $exercise['id'])->get()->getRowArray()['exercise_submitted']; 
        $exercise_submitted = json_decode($exercise_submitted, true, JSON_UNESCAPED_UNICODE);
        if(!empty($exercise_submitted) && $exercise_submitted['totals']['total'] > $exercise['data']['totals']['total']){
            return $exercise_submitted['data'];
        } else {
            return $exercise['data'];
        }
    }
    protected function jsonPrepare (array $data)
    {
        if ( isset($data['data']['lesson_pages']) ){
            $data['data']['lesson_pages'] = json_encode($data['data']['lesson_pages'], JSON_UNESCAPED_UNICODE);
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