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
            $classroom_id = session()->get('user_data')['settings']['classroom_id'];
        }
        $result = $ClassroomModel->getItem($classroom_id);

        if ($result === 'not_found') {
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
        if ($mode === 'by_user') {
            $user_id = session()->get('user_id');
        }
        $data = [
            'user_id' => $user_id,
            'limit' => $limit,
            'offset' => $offset
        ];
        $result = $ClassroomModel->getList($data);
        
        if(empty($result)){
            return $this->failNotFound('not_found');
        }

        return $this->respond($result, 200);
    }
    public function saveItem()
    {
        $ClassroomModel = model('ClassroomModel');
        $data = $this->request->getJSON(true);

        $result = $ClassroomModel->updateItem($data);

        if ($result === 'forbidden') {
            return $this->failForbidden();
        }
        if($ClassroomModel->errors()){
            return $this->failValidationErrors(json_encode($ClassroomModel->errors()));
        }

        return $this->respond($result);
    }
    public function createItem()
    {
        $ClassroomModel = model('ClassroomModel');
        $ClassroomUsermapModel = model('ClassroomUsermapModel');

        $classroom_id = $ClassroomModel->createItem();

        if ($classroom_id === 'forbidden') {
            return $this->failForbidden();
        }

        if($ClassroomModel->errors()){
            return $this->failValidationErrors(json_encode($ClassroomModel->errors()));
        }
        $ClassroomUsermapModel->itemCreate(session()->get('user_id'), $classroom_id);

        return $this->respond($classroom_id);
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
        $ClassroomUsermapModel = model('ClassroomUsermapModel');
        $ClassroomModel = model('ClassroomModel');

        $code = $this->request->getVar('classroom_code');
        $user_id = $this->request->getVar('user_id');
        if(!$user_id){
            $user_id = session()->get('user_id');
        }

        $is_disabled = false;
        $classroom = $ClassroomModel->where('code', $code)->get()->getRowArray();
        if (empty($classroom['id'])) {
            return $this->failNotFound('not_found');
        }
        if((bool) $classroom['is_private'] && $classroom['owner_id'] != session()->get('user_id') ){
            $is_disabled = true;
        }
        $existing_subscriber = $ClassroomUsermapModel->getItem($user_id, $classroom['id']);
        if(empty($existing_subscriber)){
            $result = $ClassroomUsermapModel->createItem($user_id, $classroom['id'], $is_disabled);
        } else {
            $data = [
                'user_id' => $user_id,
                'item_id' => $classroom['id'],
                'is_disabled' => $is_disabled,
            ];
            $result = $ClassroomUsermapModel->updateItem($data);
        }

        if($ClassroomUsermapModel->errors()){
            return $this->failValidationErrors(json_encode($ClassroomUsermapModel->errors()));
        }
        return $this->respond($result);
    }
    public function unsubscribe(){
        $ClassroomUsermapModel = model('ClassroomUsermapModel');
        $ClassroomModel = model('ClassroomModel');

        $code = $this->request->getVar('classroom_code');
        $user_id = $this->request->getVar('user_id');
        if(!$user_id){
            $user_id = session()->get('user_id');
        }

        $classroom_id = $ClassroomModel->checkIfExists($code);
        if (!$classroom_id) {
            return $this->failNotFound('not_found');
        }
        $result = $ClassroomUsermapModel->itemDelete($user_id, $classroom_id);

        if($ClassroomUsermapModel->errors()){
            return $this->failValidationErrors(json_encode($ClassroomUsermapModel->errors()));
        }
        return $this->respond($result);
    }
    
    public function getSubscribers()
    {
        $ClassroomModel = model('ClassroomModel');

        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $classroom_id = $this->request->getVar('classroom_id');

        if(!$classroom_id){
            $classroom_id = session()->get('user_data')['settings']['classroom_id'];
        }

        $data = [
            'limit' => $limit,
            'offset' => $offset,
            'classroom_id' => $classroom_id
        ];
        $subscribers = $ClassroomModel->getSubscribers($data);
        
        if ($subscribers == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($subscribers);
    }
    
    public function getFeed()
    {
        $ClassroomFeedModel = model('ClassroomFeedModel');

        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');

        $data = [
            'limit' => $limit,
            'offset' => $offset
        ];
        $feed = $ClassroomFeedModel->getFeed($data);
        
        if ($feed == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($feed);
    }

}
