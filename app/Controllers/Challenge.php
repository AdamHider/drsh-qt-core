<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Challenge extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $ChallengeModel = model('ChallengeModel');

        $user_id = $this->request->getVar('user_id');

        if( !$user_id ){
            $user_id = session()->get('user_id');
        }

        $user = $ChallengeModel->getItem($user_id);

        if ($user == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($user);
    }
    public function getList()
    {
        $ChallengeModel = model('ChallengeModel');

        $mode = $this->request->getVar('mode');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');

        $data = [
            'user_id' => false,
            'limit' => $limit,
            'offset' => $offset
        ];
        if($mode == 'user'){
            $data['user_id'] = session()->get('user_id');
        }
        $result = $ChallengeModel->getList($data);
        
        return $this->respond($result, 200);
    }

}
