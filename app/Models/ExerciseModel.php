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
        'skip_attempts'     => 2,
        'back_attempts'     => 3,
        'again_attempts'    => 3,
        'current_page'      => 0,
        'skipped_pages'     => [],
        'answers'   => [],
        'totals'    => [
            'total'       => 0
        ]
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $beforeInsert = ['jsonPrepare'];
    protected $beforeUpdate = ['jsonPrepare'];

    public function getItem ($exercise_id) 
    {
        $exercise = $this->select('exercises.*, COALESCE(exercises.exercise_pending, exercises.exercise_submitted) as data')
        ->where('id', $exercise_id)->get()->getRowArray();
        if(!empty($exercise)){
            $exercise['lesson_pages'] = json_decode($exercise['lesson_pages'], true, JSON_UNESCAPED_UNICODE);
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
            return json_decode($exercise['data'], true, JSON_UNESCAPED_UNICODE);
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
        $lesson['pages'] = (array) json_decode($lesson['pages'], true, JSON_UNESCAPED_UNICODE);
        $this->transBegin();
        $data = [
            'lesson_id' => $lesson_id,
            'user_id' => session()->get('user_id'),
            'lesson_pages' => $lesson['pages'],
            'exercise_pending' => $this->empty_data,
            'exercise_submitted' =>  [],
            'points' => 0,
            'attempts' => 0,
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
                $data['exercise_pending'] = $data['data'];
                $data['began_at'] = date("Y-m-d H:i:s");
                $data['finished_at'] = NULL;
            } 
            if($action == 'finish'){
                $data['exercise_pending'] = NULL;
                $data['finished_at'] = date("Y-m-d H:i:s");
                
                $data['data']['totals'] = $this->totalsCalculate($data);
                
                $exercise_submitted = $this->select('exercise_submitted')->where('id', $data['id'])->get()->getRowArray()['exercise_submitted']; 
                $exercise_submitted = json_decode($exercise_submitted, true, JSON_UNESCAPED_UNICODE);
                if(!empty($exercise_submitted) && $exercise_submitted['totals']['total'] > $data['data']['totals']['total']){
                    $data['exercise_submitted'] = $exercise_submitted['data'];
                } else {
                    $data['exercise_submitted'] = $data['data'];
                }
                $data['points'] = $data['exercise_submitted']['totals']['total'];
                $data['began_at'] = NULL;
            }
        } else {
            $data['exercise_pending'] = $data['data'];
            $data['began_at'] = NULL;
            $data['finished_at'] = NULL;
        }
        if(empty($data['answers'])){
            $data['answers'] = [];
        }
        if(empty($data['exercise_pending'])){
            $data['exercise_pending'] = NULL;
        }
        if(empty($data['exercise_submitted'])){
            $data['exercise_submitted'] = NULL;
        }
        $this->transBegin();
        $this->set($data);
        $this->where('id', $data['id']);
        $result = $this->update();

        $this->transCommit();

        return $result;        
    }
    public function exerciseGetSubmitted($exercise_id)
    {

    }

    private function totalsCalculate($exercise)
    {
        $data = [];
        $data['total'] = $exercise['data']['totals']['total'];
        $data['exercises'] = $exercise['data']['totals']['total'];
        $data['total_pages'] = count($exercise['data']['answers']);
        if(!empty($exercise['finished_at'])){
            $start_date = new Time($exercise['began_at']);
            $finish_date = new Time($exercise['finished_at']);
            $diff = $start_date->difference($finish_date);
            $time_difference = $diff->hours.''.$diff->minutes.''.$diff->seconds;
            $time_difference_seconds = strtotime($exercise['finished_at']) - strtotime($exercise['began_at']);
            $more_than_day = $time_difference_seconds/3600 > 24;
            if($more_than_day){
                $time_difference = '+24:00:00';
            }
            $extra_points = 200 - ceil($time_difference_seconds/60*7);
            if($extra_points < 0 || $time_difference_seconds < 0){
                $extra_points = 10;
            }
            if($extra_points > 200){
                $extra_points = 200;
            }
            $data['total'] += $extra_points;
            $data['time'] = $extra_points;
            $data['time_difference'] = $time_difference;
        } else {
            $data['time'] = 0;
        }
        if(!empty($exercise['attempts']) && $exercise['attempts'] !== 0){
            $extra_points = $exercise['attempts']*10;
            if($extra_points > 50){
                $extra_points = 50;
            }
            $data['total'] += $extra_points;
            $data['attempts'] = $extra_points;
            $data['attempts_count'] = $exercise['attempts'];
        } else {
            $data['attempts'] = 0;
            $data['attempts_count'] = 1;
        }
        return $data;
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