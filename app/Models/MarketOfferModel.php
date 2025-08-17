<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Events\Events;

class MarketOfferModel extends Model
{
    protected $table      = 'market_offers';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';

    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getList () 
    {
        $DescriptionModel = model('DescriptionModel');
        $ResourceModel = model('ResourceModel');
        
        $market_offers = $this->orderBy('order')->get()->getResultArray();
        
        if(empty($market_offers)){
            return false;
        }
        foreach($market_offers as &$market_offer){
            $reward_config = json_decode($market_offer['reward_config'], true);
            $market_offer = array_merge($market_offer, $DescriptionModel->getItem('market_offer', $market_offer['id']));
            if(!empty($reward_config)){
                $market_offer['reward'] = $ResourceModel->proccessItemReward($reward_config);
            }
            $market_offer['image'] = base_url('image/index.php'.$market_offer['image']);
            $market_offer['background_image'] = base_url('image/index.php'.$market_offer['background_image']);
            unset($market_offer['reward_config']);
        } 

        return $market_offers;
    }
    

    public function buyItem($offer_id)
    {
        $ResourceModel = model('ResourceModel');

        /*CHECK PAYMENT SUCCESS...*/
        $payment_succeed = true;
        /*CHECK PAYMENT SUCCESS...*/
        if($payment_succeed){
            $market_offer = $this->where('id', $offer_id)->get()->getRowArray();
            if(!empty($market_offer)){
                $reward = json_decode($market_offer['reward_config'], true);
                if($ResourceModel->enrollUserList(session()->get('user_id'), $reward)){
                    return $offer_id;
                };
            }
        }
        return false;
    }
}