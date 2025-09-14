<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Chest extends BaseController
{
    use ResponseTrait;
    
    public function getList()
    {
        $ChestModel = model('ChestModel');
        
        $type = $this->request->getVar('type') ?? 'daily';

        $data = [
            'type' => $type
        ];
        $result = $ChestModel->getList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function buyItem()
    {
        $ChestModel = model('ChestModel');

        $offer_id = $this->request->getVar('offer_id');

        $result = $ChestModel->buyItem($offer_id);
        if($result == 'forbidden'){
            return $this->failForbidden('forbidden');
        }
        if($result == 'not_found'){
            
            return $this->failNotFound('not_found');
        }
        if(!$result){
            return $this->fail();
        }
        return $this->respond($result, 200);
    }

}
