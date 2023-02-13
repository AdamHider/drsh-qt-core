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

        return $this->respond($challenge);
    }
    public function getList()
    {
        $ChallengeModel = model('ChallengeModel');

        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');

        $data = [
            'limit' => $limit,
            'offset' => $offset
        ];
        $challenges = $ChallengeModel->getList($data);
        
        if ($challenges == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($challenges);
    }

}
