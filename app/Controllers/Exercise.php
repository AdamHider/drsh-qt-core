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
        $ExerciseStatisticModel = model('ExerciseStatisticModel');

        $mode = $this->request->getVar('mode');
        $by_classroom = $this->request->getVar('by_classroom');
        $time_period = $this->request->getVar('time_period');


        $data = [
            'time_period' => $time_period,
            'by_classroom' => $by_classroom
        ];

        $result = $ExerciseStatisticModel->getLeaderboard($mode, $data);
        return $this->respond($result, 200);
    }
    
}
