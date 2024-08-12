<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Exercise extends BaseController
{
    use ResponseTrait;

    public function addItem()
    {
        $ExerciseModel = model('ExerciseModel');

        $lesson_id = $this->request->getVar('lesson_id');
        if (!$lesson_id) {
            return $this->fail('no_lesson');
        }
        $exercise_id = $ExerciseModel->createItem($lesson_id);
        
        if ($exercise_id == 'not_found') {
            return $this->failNotFound('not_found');
        }
        if ($exercise_id == 'bad_request') {
            return $this->fail('bad_request');
        }
        if($ExerciseModel->errors()){
            return $this->failValidationErrors(json_encode($ExerciseModel->errors()));
        }
        return $this->respond($exercise_id);
    }
    public function redoItem()
    {
        $ExerciseModel = model('ExerciseModel');

        $lesson_id = $this->request->getVar('lesson_id');
        if (!$lesson_id) {
            return $this->fail('no_lesson');
        }
        $exercise_id = $ExerciseModel->redoItem($lesson_id);
        
        if (!$exercise_id) {
            return $this->failNotFound('not_found');
        }
        if($ExerciseModel->errors()){
            return $this->failValidationErrors(json_encode($ExerciseModel->errors()));
        }
        return $this->respond($exercise_id);
    }
    public function saveAnswer()
    {
        $ExerciseAnswerModel = model('ExerciseAnswerModel');

        $lesson_id = $this->request->getVar('lesson_id');
        $data = (array) $this->request->getVar('data');

        $result = $ExerciseAnswerModel->saveAnswer($lesson_id, $data);
        if($ExerciseAnswerModel->errors()){
            return $this->failValidationErrors(json_encode($ExerciseAnswerModel->errors()));
        }
        return $this->respond($result, 200);
    }
    public function getLeaderboard()
    {
        $ChallengeModel = model('ChallengeModel');
        $ExerciseStatisticModel = model('ExerciseStatisticModel');

        $mode = $this->request->getVar('mode');
        $classroom_id = $this->request->getVar('classroom_id');
        $by_classroom = $this->request->getVar('by_classroom');
        $lesson_id = $this->request->getVar('lesson_id');
        $challenge_id = $this->request->getVar('challenge_id');
        $time_period = $this->request->getVar('time_period');
        $user_only = $this->request->getVar('user_only');
        if($by_classroom && !$classroom_id){
            $classroom_id = session()->get('user_data')->profile->classroom_id;
        }
        $data = [
            'time_period' => $time_period,
            'user_only' => $user_only,
            'classroom_id' => $classroom_id,
            'lesson_id' => $lesson_id,
            'order_by' => false
        ];

        if($challenge_id){
            $challenge = $ChallengeModel->where('id', $challenge_id)->get()->getRowArray();
            if(!empty($challenge)){
                $data['classroom_id'] = $challenge['classroom_id'];
                $data['date_start'] = $challenge['date_start'];
                $data['date_end'] = $challenge['date_end'];
                $data['winner_limit'] = $challenge['winner_limit'];
                if($challenge['code'] == 'total_points_first'){
                    $data['order_by'] = 'finished_at';
                }
            }
        }
        $leaderboard = $ExerciseStatisticModel->getLeaderboard($mode, $data);
        
        if (!$leaderboard) {
            return $this->failNotFound('not_found');
        }
        return $this->respond($leaderboard, 200);
    }
    
}
