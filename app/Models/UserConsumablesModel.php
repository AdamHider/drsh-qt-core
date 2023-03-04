<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class UserConsumablesModel extends Model
{
    protected $table      = 'user_consumables';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'user_id', 
        'code', 
        'quantity', 
        'is_restorable', 
        'consumed_at'
    ];
    
    protected $useTimestamps = false;
    private $config = [];

    public function getList ($user_id) 
    {
        $this->getLevelConfig($user_id);   
        $this->checkRestoration($user_id);
        $consumables = $this->where('user_id', $user_id)->get()->getResultArray();

        $result = [];
        foreach($consumables as &$consumable){
            $item = [
                'quantity'      => $consumable['quantity'],
                'is_restorable' => $consumable['is_restorable']
            ];
            if((bool) $consumable['is_restorable']){
                $item['next_restoration'] = $this->getNextRestorationTime($consumable['code'], $consumable['consumed_at']);
                $item['total_time_cost'] = $this->config[$consumable['code']]['restoration'];
                $item['total'] = $this->config[$consumable['code']]['total'];
                $item['percentage'] = ($item['total_time_cost'] - $item['next_restoration']) * 100 / $item['total_time_cost'];
            }
            $result[$consumable['code']] = $item;
        }
        return $result;
    }
    public function checkRestoration ($user_id)
    {
        $restorable_consumables = $this->where('user_id', $user_id)->where('is_restorable', 1)->get()->getResultArray();
        foreach($restorable_consumables as $consumable){
            $time_cost = $this->config[$consumable['code']]['restoration'];
            $total = $this->config[$consumable['code']]['total'];
            if(!$consumable['consumed_at'] && $consumable['quantity'] == $total){
                continue;
            }
            if(!$consumable['consumed_at']){
                $consumable['consumed_at'] = Time::now()->toDateTimeString();;
            }
            $consumed_at = Time::parse($consumable['consumed_at'], Time::now()->getTimezone());
            $time_difference = $consumed_at->difference(Time::now())->getSeconds();
            $result = [
                'quantity' => 0,
                'consumed_at' => ''
            ];
            if($time_difference >= 0){
                $restorated_quantity = floor($time_difference / $time_cost);
                $new_quantity = $consumable['quantity'] + $restorated_quantity;
                if($new_quantity >= $total){
                    $result['quantity'] = $new_quantity;
                    $result['consumed_at'] = null;
                } else {
                    $consumed_at = $consumed_at->addSeconds($restorated_quantity * $time_cost);
                    $result['quantity'] = $new_quantity;
                    $result['consumed_at'] = $consumed_at->toDateTimeString();
                }
                $this->update($consumable['id'], $result);
            }
        }      
    }
    public function getNextRestorationTime ($code, $consumed_at)
    {
        $time_cost = $this->config[$code]['restoration'];
        if($consumed_at){
            $consumed_at = Time::parse($consumed_at, Time::now()->getTimezone());
            $next_consumed_at = $consumed_at->addSeconds($time_cost);
            return Time::now()->difference($next_consumed_at)->getSeconds();
        } else {
            return 0;
        }
    }
    public function getLevelConfig ($user_id)
    {
        $UserExperienceModel = model('UserExperienceModel');
        $level = $UserExperienceModel->getItem($user_id);
        $this->config = $level['level_config']['consumables'];
    }
    public function createItem ($user_id)
    {
        $this->transBegin();
        
        $data = [
            'user_id'       => $user_id,
            'character_id'  => getenv('user_consumables.character_id'),
            'classroom_id'  => NULL,
            'course_id'     => NULL
            
        ];
        $user_consumables_id = $this->insert($data, true);

        $this->transCommit();

        return $user_consumables_id;        
    }
    public function updateItem ($data)
    {
        $this->transBegin();

        $this->set($data);
        $this->where('user_id', $data['user_id']);
        $result = $this->update();

        $this->transCommit();

        return $result;        
    }


}