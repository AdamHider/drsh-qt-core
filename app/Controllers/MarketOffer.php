<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class MarketOffer extends BaseController
{
    use ResponseTrait;
    
    public function getList()
    {
        $MarketOfferModel = model('MarketOfferModel');

        $result = $MarketOfferModel->getList();
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function buyItem()
    {
        $MarketOfferModel = model('MarketOfferModel');

        $offer_id = $this->request->getVar('offer_id');

        $result = $MarketOfferModel->buyItem($offer_id);
        if(!$result){
            return $this->fail();
        }
        return $this->respond($result, 200);
    }

}
