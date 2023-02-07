<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Exercise extends BaseController
{
    use ResponseTrait;

    public function getItem()
    {
        $ExerciseModel = model('ExerciseModel');

        $exercise_id = $this->request->getVar('exercise_id');
        
        $exercise = $ExerciseModel->getItem($exercise_id);
        
        if ($exercise == 'not_found') {
            return $this->failNotFound('not_found');
        }
        return $this->respond($exercise);
    }
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
        return $this->respond($exercise_id);
    }
    public function getList()
    {
        $ExerciseModel = model('ExerciseModel');

        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');

        $data = [
            'limit' => $limit,
            'offset' => $offset
        ];

        $result = $ExerciseModel->getList($data);
        return $this->respond($result, 200);
    }
    public function saveAnswer()
    {
        $ExerciseAnswerModel = model('ExerciseAnswerModel');

        $lesson_id = $this->request->getVar('lesson_id');
        $data = (array) $this->request->getVar('data');

        $result = $ExerciseAnswerModel->saveAnswer($lesson_id, $data);
        return $this->respond($result, 200);
    }
    
}
