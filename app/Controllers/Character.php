<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Character extends BaseController
{
    use ResponseTrait;
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
    public function selectItem()
    {
        
        $CharacterModel = model('CharacterModel');

        $character_id = $this->request->getVar('id');

        $result = $CharacterModel->selectItem($character_id);

        if ($result == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($result);
    }

}
