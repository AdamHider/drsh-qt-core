<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Events\Events;

class ChestModel extends Model
{
    protected $table      = 'chests';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';

    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getList ($data) 
    {
        $this->checkChestsReset();
        $DescriptionModel = model('DescriptionModel'); 
        $ResourceModel = model('ResourceModel');
        
        if(isset($data['type'])){
            $this->where('type', $data['type']);
        }
        $this->join('chests_usermap','chests_usermap.item_id = chests.id AND chests_usermap.user_id = '.session()->get('user_id'),'left');
        $chests = $this->select('chests.*, chests_usermap.created_at as user_created, chests_usermap.updated_at as user_updated')->orderBy('order')->get()->getResultArray();
        
        if(empty($chests)){
            return false;
        }
        foreach($chests as &$chest){
            $reward_config = json_decode($chest['reward_config'], true);
            $chest = array_merge($chest, $DescriptionModel->getItem('chest', $chest['id']));
            if(!empty($reward_config)){
                $chest['reward'] = $ResourceModel->proccessItemReward($reward_config);
            }
            $chest['image'] = base_url('image/index.php'.$chest['image']);
            $chest['background_image'] = base_url('image/index.php'.$chest['background_image']);
            if($chest['type'] == 'daily'){
                $chest['is_gained'] = (bool) $chest['user_created'];
                $chest['is_available'] = $this->checkAvailable($chest['id'], $chests);
                $chest['is_active'] = $chest['is_available'];
                if($chest['is_gained'] && date('Y-m-d',strtotime($chest['user_updated'])) == date('Y-m-d')) {
                    $chest['is_active'] = true;
                } 
            }
            unset($chest['reward_config']);
        } 

        return $chests;
    }
    

    public function buyItem($chest_id)
    {
        $ResourceModel = model('ResourceModel');
        $PaymentModel = model('PaymentModel');
        $ChestUsermapModel = model('ChestUsermapModel');
        
        $chest = $this->where('id', $chest_id)->get()->getRowArray();
        if(empty($chest)){
            return 'not_found';
        }
        if($chest['type'] == 'market'){
            
            /*CHECK PAYMENT SUCCESS...*/
            $payment_data = [
                'user_id' => session()->get('user_id'),
                'amount' => [
                    'value' => $chest['price'],
                    'currency' => 'RUB',
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => 'https://www.example.com/return_url',
                ],
                'capture' => true,
                'description' => 'Заказ №1'
            ];
            $payment_succeed = $PaymentModel->createItem($payment_data);
            /*CHECK PAYMENT SUCCESS...*/
            
            if($payment_succeed){
                if(!empty($chest)){
                    $reward = json_decode($chest['reward_config'], true);
                    if($ResourceModel->enrollUserList(session()->get('user_id'), $reward)){
                        return $chest_id;
                    };
                }
            }
            return false;
        } else 
        if ($chest['type'] == 'daily') {
            $this->join('chests_usermap','chests_usermap.item_id = chests.id AND chests_usermap.user_id = '.session()->get('user_id'),'left');
            $daily_chests = $this->select('chests.id, chests_usermap.created_at as user_created, chests_usermap.updated_at as user_updated')->where('type', 'daily')->get()->getResultArray();
            if(!$this->checkAvailable($chest_id, $daily_chests)){
                return 'forbidden';
            }
            $reward = json_decode($chest['reward_config'], true);
            if($ResourceModel->enrollUserList(session()->get('user_id'), $reward)){
                $data = [
                    'item_id' => $chest['id'],
                    'user_id' => session()->get('user_id'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                return $ChestUsermapModel->upsert($data);
            };
        }
    }
    private function checkAvailable($chest_id, $chests)
    {
        for($i = count($chests)-1; $i >= 0; $i--){
            if($chests[$i]['id'] == $chest_id){
                if(!empty($chests[$i-1]) && date('Y-m-d',strtotime($chests[$i-1]['user_updated']))  == date('Y-m-d',strtotime("-1 days"))){
                    return true;
                }
                if(empty($chests[$i-1]) && empty($chests[$i]['user_updated'])){
                    return true;
                }
            }
        }
        return false;
    }
    private function checkChestsReset()
    {
        $ChestUsermapModel = model('ChestUsermapModel');
        $last_chest = $ChestUsermapModel->selectMax('updated_at')->where('user_id', session()->get('user_id'))->get()->getRowArray();
        if(strtotime(date('Y-m-d',strtotime($last_chest['updated_at']) )) < strtotime(date('Y-m-d',strtotime("-1 days")))){
            $ChestUsermapModel->where('user_id', session()->get('user_id'))->delete();
        }
    }
    
    
}