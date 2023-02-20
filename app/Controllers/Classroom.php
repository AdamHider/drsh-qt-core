<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Classroom extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $ClassroomModel = model('ClassroomModel');

        $classroom_id = $this->request->getVar('classroom_id');

        if( !$classroom_id ){
            $classroom_id = session()->get('user_data')['profile']['classroom_id'];
        }
        
        $result = $ClassroomModel->getItem($classroom_id);

        if ($result == 'not_found') {
            return $this->failNotFound('not_found');
        }
        if ($result == 'forbidden') {
            return $this->failForbidden();
        }

        return $this->respond($result);
    }
    public function getList()
    {
        $ClassroomModel = model('ClassroomModel');

        $mode = $this->request->getVar('mode');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $user_id = false;
        if ($mode == 'by_user') {
            $user_id = session()->get('user_id');
        }
        $data = [
            'user_id' => $user_id,
            'limit' => $limit,
            'offset' => $offset
        ];
        $result = $ClassroomModel->getList($data);
        
        return $this->respond($result, 200);
    }
    public function checkIfExists()
    {
        $ClassroomModel = model('ClassroomModel');

        $code = $this->request->getVar('code');
        
        if($ClassroomModel->checkIfExists($code)){
            return $this->respond(true); 
        } 
        return $this->fail(false);
    }

    public function subscribe(){
        $ClassroomUsermap = model('ClassroomUsermap');
        $ClassroomModel = model('ClassroomModel');

        $code = $this->request->getVar('classroom_code');
        $user_id = session()->get('user_id');

        $classroom_id = $ClassroomModel->checkIfExists($code);
        if (!$classroom_id) {
            return $this->failNotFound('not_found');
        }
        $ClassroomUsermap->itemCreate($user_id, $classroom_id);

        if($ClassroomUsermap->errors()){
            return $this->failValidationErrors(json_encode($ClassroomUsermap->errors()));
        }
        $classroom = $ClassroomModel->getItem($classroom_id);
        return $this->respond($classroom);
        
    }

}
