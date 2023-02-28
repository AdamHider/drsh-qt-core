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
        if ($homework == 'forbidden') {
            return $this->failForbidden();
        }

        return $this->respond($homework);
    }
    public function getList()
    {
        $HomeworkModel = model('HomeworkModel');

        $mode = $this->request->getVar('mode');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $classroom_id = $this->request->getVar('classroom_id');

        $data = [
            'limit' => $limit,
            'offset' => $offset,
        ];
        if($mode == 'by_user'){
            $data['user_id'] = session()->get('user_id');
        }
        if($classroom_id){
            $data['classroom_id'] = $classroom_id;
        }
        
        $homeworks = $HomeworkModel->getList($data);
        
        if ($homeworks == 'not_found') {
            return $this->failNotFound('not_found');
        }
        if ($homeworks == 'forbidden') {
            return $this->failForbidden();
        }

        return $this->respond($homeworks);
    }

}
