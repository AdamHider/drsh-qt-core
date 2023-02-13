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

        $data = [
            'limit' => $limit,
            'offset' => $offset
        ];
        $homeworks = $HomeworkModel->getList($data);
        
        if ($homeworks == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($homeworks);
    }

}
