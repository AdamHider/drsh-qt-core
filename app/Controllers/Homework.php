<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Homework extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $HomeworkModel = model('HomeworkModel');

        $homework_id = $this->request->getVar('homework_id');

        $homework = $HomeworkModel->getItem($homework_id);

        if ($homework == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($homework);
    }
    public function getList()
    {
        $HomeworkModel = model('HomeworkModel');

        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $classroom_id = $this->request->getVar('classroom_id');

        if(!$classroom_id){
            $classroom_id = session()->get('user_data')->profile->classroom_id;
        }

        $data = [
            'limit' => $limit,
            'offset' => $offset,
            'classroom_id' => $classroom_id
        ];
        $homeworks = $HomeworkModel->getList($data);
        
        if ($homeworks == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($homeworks);
    }

}
