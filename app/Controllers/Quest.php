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

        $quest = $QuestModel->getItem($quest_id);

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
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $classroom_id = $this->request->getVar('classroom_id');

        $data = [
            'limit' => $limit,
            'offset' => $offset
        ];
        if($mode == 'by_user'){
            $data['user_id'] = session()->get('user_id');
        }
        if($classroom_id){
            $data['classroom_id'] = $classroom_id;
        }

        $quests = $QuestModel->getList($data);
        
        if ($quests == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($quests);
    }
}
