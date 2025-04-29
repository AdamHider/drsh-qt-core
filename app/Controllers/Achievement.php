<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Achievement extends BaseController
{
    use ResponseTrait;
    
    public function getList()
    {
        $AchievementModel = model('AchievementModel');

        $mode = $this->request->getVar('mode');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');

        $data = [
            'user_id' => false,
            'limit' => $limit,
            'offset' => $offset
        ];
        if($mode == 'by_user'){
            $data['user_id'] = session()->get('user_id');
        }
        $result = $AchievementModel->getList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

}
