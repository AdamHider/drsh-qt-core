<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Quest extends BaseController
{
    use ResponseTrait;

    public function getItem()
    {
        
        $QuestModel = model('QuestModel');

        $quest_id = $this->request->getVar('quest_id');

        $data = [
            'user_id' => session()->get('user_id'),
            'quest_id' => $quest_id
        ];
        $quest = $QuestModel->getItem($data);

        if ($quest == 'not_found') {
            return $this->failNotFound('not_found');
        }
        if ($quest == 'forbidden') {
            return $this->failForbidden();
        }

        return $this->respond($quest);
    }
    public function getList()
    {
        $QuestModel = model('QuestModel');

        $mode = $this->request->getVar('mode');
        $active_only = $this->request->getVar('active_only');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $classroom_id = $this->request->getVar('classroom_id');

        $data = [
            'user_id' => session()->get('user_id'),
            'limit' => $limit,
            'offset' => $offset
        ];
        if($active_only){
            $data['active_only'] = $active_only;
        }
        if($classroom_id){
            $data['classroom_id'] = $classroom_id;
        }

        $quests = $QuestModel->getList($data);
        
        if ($quests == 'not_found') {
            return $this->failNotFound('not_found');
        }
        $this->response->setHeader('Data-Hash', md5(json_encode($quests)));
        return $this->respond($quests);
    }
    public function claimReward()
    {
        $QuestModel = model('QuestModel');

        $quest_id = $this->request->getVar('quest_id');
        $data = [
            'user_id' => session()->get('user_id'),
            'quest_id' => $quest_id
        ];
        $result = $QuestModel->claimReward($data);
        
        if ($result == 'not_found') {
            return $this->failNotFound('not_found');
        }
        if ($result == 'forbidden') {
            return $this->failForbidden();
        }
        if($QuestModel->errors()){
            return $this->failValidationErrors($QuestModel->errors());
        }
        return $this->respond($result);
    }
    public function startItem()
    {
        $QuestModel = model('QuestModel');
        $QuestsUsermapModel = model('QuestsUsermapModel');

        $quest_id = $this->request->getVar('quest_id');

        $data = [
            'item_id' => $quest_id,
            'user_id' => session()->get('user_id'),
            'status' => 'active'
        ];
        $result = $QuestsUsermapModel->save($data);
        
        if ($result == 'not_found') {
            return $this->failNotFound('not_found');
        }
        if ($result == 'forbidden') {
            return $this->failForbidden();
        }
        if($QuestModel->errors()){
            return $this->failValidationErrors($QuestModel->errors());
        }
        return $this->respond($result);
    }
    public function saveItem()
    {
        $QuestModel = model('QuestModel');
        $data = $this->request->getJSON(true);

        $result = $QuestModel->updateItem($data);

        if ($result === 'forbidden') {
            return $this->failForbidden();
        }
        if($QuestModel->errors()){
            return $this->failValidationErrors($QuestModel->errors());
        }
        return $this->respond($result);
    }
    public function createItem()
    {
        $QuestModel = model('QuestModel');
        //$ClassroomUsermapModel = model('ClassroomUsermapModel');

        $classroom_id = $this->request->getVar('classroom_id');

        $data = [
            'classroom_id' => $classroom_id
        ];

        $quest_id = $QuestModel->createItem($data);

        if ($quest_id === 'forbidden') {
            return $this->failForbidden();
        }

        if($QuestModel->errors()){
            return $this->failValidationErrors($QuestModel->errors());
        }
        //$ClassroomUsermapModel->itemCreate(session()->get('user_id'), $quest_id);

        return $this->respond($quest_id);
    }
    public function getAvailableLessons()
    {
        $QuestModel = model('QuestModel');

        $title = $this->request->getVar('title');

        $data = [
            'title' => $title
        ];

        $result = $QuestModel->getAvailableLessons($data);
        return $this->respond($result, 200);
    }
    public function calculateReward()
    {
        $QuestModel = model('QuestModel');

        $quest_id = $this->request->getVar('quest_id');
        $code = $this->request->getVar('code');
        $value = $this->request->getVar('value');

        $data = [
            'quest_id' => $quest_id,
            'code' => $code,
            'value' => (int) $value
        ];

        $result = $QuestModel->calculateReward($data);
        return $this->respond($result, 200);
    }
    public function getAvailableCodes()
    {
        $QuestModel = model('QuestModel');

        $classroom_id = $this->request->getVar('classroom_id');

        $data = [
            'classroom_id' => $classroom_id
        ];

        $result = $QuestModel->getAvailableCodes($data);
        return $this->respond($result, 200);
    }
    
    
}
