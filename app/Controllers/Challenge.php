<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Challenge extends BaseController
{
    use ResponseTrait;

    public function getItem()
    {
        
        $ChallengeModel = model('ChallengeModel');

        $challenge_id = $this->request->getVar('challenge_id');

        $challenge = $ChallengeModel->getItem($challenge_id);

        if ($challenge == 'not_found') {
            return $this->failNotFound('not_found');
        }
        if ($challenge == 'forbidden') {
            return $this->failForbidden();
        }

        return $this->respond($challenge);
    }
    public function getList()
    {
        $ChallengeModel = model('ChallengeModel');

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

        $challenges = $ChallengeModel->getList($data);
        
        if ($challenges == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($challenges);
    }
    
    public function addWinnerItem()
    {
        $ChallengeWinnerModel = model('ChallengeWinnerModel');

        $challenge_id = $this->request->getVar('challenge_id');
        if (!$challenge_id) {
            return $this->fail('no_challenge');
        }
        if(!session()->get('user_data')->phone){
            return $this->fail('no_phone');
        }
        $data = [
            'challenge_id' => $challenge_id,
            'user_id' => session()->get('user_id'),
            'status' => 0 
        ];
        $winner_id = $ChallengeWinnerModel->createItem($data);
        
        if ($winner_id == 'not_found') {
            return $this->failNotFound('not_found');
        }
        if($ChallengeWinnerModel->errors()){
            return $this->failValidationErrors(json_encode($ChallengeWinnerModel->errors()));
        }
        return $this->respond($winner_id);
    }
}
