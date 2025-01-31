<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Quest extends BaseController
{
    use ResponseTrait;

    public function getList()
    {
        $QuestModel = model('QuestModel');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $data = [
            'user_id' => session()->get('user_id'),
            'limit' => $limit,
            'offset' => $offset,
            'active_only' => true
        ];
        $quests = $QuestModel->getList($data);
        
        if ($quests === 'not_found') {
            return $this->failNotFound('not_found');
        }
        $this->response->setHeader('Data-Hash', md5(json_encode($quests)));
        return $this->respond($quests);
    }
    public function claimReward()
    {
        $QuestModel = model('QuestModel');

        $quest_id = $this->request->getVar('quest_id');
        
        $result = $QuestModel->claimReward($quest_id);
        
        if ($result === 'not_found') {
            return $this->failNotFound('not_found');
        }
        if ($result === 'forbidden') {
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

        $quest_id = $this->request->getVar('quest_id');

        $data = [
            'item_id' => $quest_id,
            'user_id' => session()->get('user_id'),
            'status' => 'active'
        ];
        $result = $QuestModel->updateUserItem($data);
        if ($result === 'not_found') {
            return $this->failNotFound('not_found');
        }
        if ($result === 'forbidden') {
            return $this->failForbidden();
        }
        if($QuestModel->errors()){
            return $this->failValidationErrors($QuestModel->errors());
        }
        return $this->respond($result);
    }
    
}
