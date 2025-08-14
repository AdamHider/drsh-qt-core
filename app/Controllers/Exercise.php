<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Exercise extends BaseController
{
    use ResponseTrait;

    public function createItem()
    {
        $ExerciseModel = model('ExerciseModel');

        $lesson_id = $this->request->getVar('lesson_id');
        if (!$lesson_id) {
            return $this->fail('no_lesson');
        }
        
        $exercise_id = $ExerciseModel->createItem($lesson_id);
        
        if (!$exercise_id) {
            return $this->fail('fail');
        }
        if ($exercise_id == 'not_found') {
            return $this->failNotFound('not_found');
        }
        if ($exercise_id == 'not_enough_resources') {
            return $this->fail('not_enough_resources');
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
        $time = (int) $this->request->getVar('time');

        $result = $ExerciseAnswerModel->saveAnswer($lesson_id, $data, $time);
        if($ExerciseAnswerModel->errors()){
            return $this->failValidationErrors(json_encode($ExerciseAnswerModel->errors()));
        }
        return $this->respond($result, 200);
    }
    public function getLeaderboard()
    {
        $ExerciseStatisticModel = model('ExerciseStatisticModel');

        $lesson_id = $this->request->getVar('lesson_id');
        $time_period = $this->request->getVar('time_period');
        $user_only = $this->request->getVar('user_only');

        $data = [
            'time_period' => $time_period,
            'user_only' => $user_only,
            'lesson_id' => $lesson_id,
            'order_by' => false
        ];

        $leaderboard = $ExerciseStatisticModel->getLeaderboard($data);
        
        if (!$leaderboard) {
            return $this->failNotFound('not_found');
        }
        return $this->respond($leaderboard, 200);
    }
    
}
