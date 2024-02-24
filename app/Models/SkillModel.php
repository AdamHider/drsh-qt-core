<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class ResourceModel extends Model
{
    protected $table      = 'resources';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'item_id', 
        'user_id', 
        'quantity', 
        'consumed_at'
    ];
    
    protected $useTimestamps = false;
    private $config = [];

    public function getList ($user_id) 
    {
        $this->getLevelConfig($user_id);   
        $this->checkRestoration($user_id);
        $resources = $this->where('user_id', $user_id)->get()->getResultArray();

        $result = [];
        foreach($resources as &$resource){
            $item = [
                'quantity'      => $resource['quantity'],
                'is_restorable' => (bool) $resource['is_restorable']
            ];
            if($resource['is_restorable']){
                $item['next_restoration'] = $this->getNextRestorationTime($resource['code'], $resource['consumed_at']);
                $item['total_time_cost'] = $this->config[$resource['code']]['restoration'];
                $item['total'] = $this->config[$resource['code']]['total'];
                $item['percentage'] = ($item['total_time_cost'] - $item['next_restoration']) * 100 / $item['total_time_cost'];
            }
            $result[$resource['code']] = $item;
        }
        return $result;
    }
    public function getItem ($code, $user_id, $item_id) 
    {
        $resource = $this->where('user_id', $user_id)->where('item_id', $item_id)->where('code', $code)->get()->getResultArray();
        return $resource;
    }
    public function checkRestoration ($user_id)
    {
        $restorable_resources = $this->where('user_id', $user_id)->where('is_restorable', 1)->get()->getResultArray();
        foreach($restorable_resources as $resource){
            $time_cost = $this->config[$resource['code']]['restoration'];
            $total = $this->config[$resource['code']]['total'];
            if(!$resource['consumed_at'] && $resource['quantity'] == $total){
                continue;
            }
            if(!$resource['consumed_at']){
                $resource['consumed_at'] = Time::now()->toDateTimeString();
            }
            $consumed_at = Time::parse($resource['consumed_at'], Time::now()->getTimezone());
            $time_difference = $consumed_at->difference(Time::now())->getSeconds();
            $result = [
                'quantity' => 0,
                'consumed_at' => ''
            ];
            if($time_difference >= 0){
                $restorated_quantity = floor($time_difference / $time_cost);
                $new_quantity = $resource['quantity'] + $restorated_quantity;
                if($new_quantity >= $total){
                    $result['quantity'] = $total;
                    $result['consumed_at'] = null;
                } else {
                    $consumed_at = $consumed_at->addSeconds($restorated_quantity * $time_cost);
                    $result['quantity'] = $new_quantity;
                    $result['consumed_at'] = $consumed_at->toDateTimeString();
                }
                $this->update($resource['id'], $result);
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
        $UserLevelModel = model('UserLevelModel');
        $level = $UserLevelModel->getItem($user_id);
        if($level){
            $this->config = $level['level_config']['resources'];
        }
        
    }
    public function substract ($user_id, $resources)
    {
        if(!$this->checkResources($user_id, $resources)){
            return false;
        }
        $user_resources = $this->where('user_id', $user_id)->get()->getResultArray();
        foreach($user_resources as &$resource_data){
            if(!isset($resources[$resource_data['code']])){
                continue;
            }
            if($resource_data['quantity'] == 0){
                return false;
            }
            $resource_data['quantity'] = $resource_data['quantity'] - $resources[$resource_data['code']];
            if(!$resource_data['consumed_at']){
                $resource_data['consumed_at'] = Time::now()->toDateTimeString();
            }
            $this->updateItem($resource_data);      
        }  
        return true;
    }
    
    public function checkResources ($user_id, $resources)
    {
        $user_resources = $this->getList(session()->get('user_id'));
        foreach($user_resources as $resource_title => $resource_data){
            if(!isset($resources[$resource_title])){
                continue;
            }
            if($resources[$resource_title] > $resource_data['quantity']){
                return false;
            }
        }
        return true;
    }
    public function createItem ($user_id)
    {
        $this->transBegin();
        
        $data = [
            'user_id'       => $user_id,
            'character_id'  => getenv('user_resources.character_id'),
            'classroom_id'  => NULL,
            'course_id'     => NULL
            
        ];
        $user_resources_id = $this->insert($data, true);

        $this->transCommit();

        return $user_resources_id;        
    }
    public function updateItem ($data)
    {
        $this->transBegin();

        $result = $this->update(['id'=>$data['id']], $data);

        $this->transCommit();

        return $result;        
    }


}