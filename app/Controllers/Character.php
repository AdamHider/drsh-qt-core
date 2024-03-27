<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Character extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $UserModel = model('UserModel');

        $user_id = $this->request->getVar('user_id');

        if( !$user_id ){
            $user_id = session()->get('user_id');
        }

        $user = $UserModel->getItem($user_id);

        if ($user == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($user);
    }
    public function getList()
    {
        $CharacterModel = model('CharacterModel');

        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');

        $data = [];
        if($limit && $offset){
            $data['limit'] = $limit;
            $data['offset'] = $offset;
        }
        $characters = $CharacterModel->getList($data);
        
        if ($characters == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($characters);
    }

}
