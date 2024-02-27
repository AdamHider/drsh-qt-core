<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class ResourceModel extends Model
{
    protected $table      = 'resources';
    protected $useAutoIncrement = true;

    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'code', 
        'is_restorable'
    ];
    
    protected $useTimestamps = false;

    private $settings;
    public function __construct()
    {
        if(session()->get('user_id')){
            $UserSettingsModel = model('UserSettingsModel');
            $this->settings = $UserSettingsModel->getList(['user_id' => session()->get('user_id')]);
            $this->recalculateRestoration(session()->get('user_id'));
        }
    }
    public function getList ($data) 
    {
        $resources = $this->select('resources.id, resources.code, resources.is_restorable, COALESCE(resources_usermap.quantity, 0) quantity, resources_usermap.consumed_at')
        ->join('resources_usermap', 'resources_usermap.item_id = resources.id AND resources_usermap.user_id = '.$data['user_id'], 'left')->get()->getResultArray();
        
        $result = [];
        
        foreach($resources as &$resource){
            $result[$resource['code']] = [
                'quantity'      => $resource['quantity'],
                'is_restorable' => (bool) $resource['is_restorable'],
                'restoration'   =>  $this->getItemRestoration($resource)
            ];
        }
        return $result;
    }
    
    public function getItem ($code, $user_id, $item_id) 
    {
        return $this->where('user_id', $user_id)->where('item_id', $item_id)->where('code', $code)->get()->getResultArray();
    }

    public function getItemRestoration ($resource)
    {   
        if(!(bool)$resource['is_restorable'] || !$resource['consumed_at']) return null;

        $restorationTime = $this->settings[$resource['code'].'RestorationTime'];
        $maxValue = $this->settings[$resource['code'].'MaxValue'];

        return [
            'nextRestoration' => $this->getNextRestoration($restorationTime, Time::parse($resource['consumed_at'], Time::now()->getTimezone())),
            'restorationTime' => (int) $restorationTime,
            'maxValue' => (int) $maxValue
        ];
    }
    
    private function recalculateRestoration ($user_id)
    {
        $resources = $this->join('resources_usermap', 'resources_usermap.item_id = resources.id AND resources_usermap.user_id = '.$user_id)
        ->where('is_restorable', 1)->get()->getResultArray();

        $ResourceUsermapModel = model('ResourceUsermapModel');
        foreach($resources as $resource){
            if(!$resource['consumed_at']) $resource['consumed_at'] = Time::now()->toDateTimeString();

            $restorationTime = $this->settings[$resource['code'].'RestorationTime'];
            $maxValue = $this->settings[$resource['code'].'MaxValue'];
    
            $consumptionTime = Time::parse($resource['consumed_at'], Time::now()->getTimezone());
            $timeDifference = $consumptionTime->difference(Time::now())->getSeconds();
            
            if($timeDifference < 0) continue;
    
            $restoratedValue = floor($timeDifference / $restorationTime);
            $newValue = $resource['quantity'] + $restoratedValue;
            if($newValue >= $maxValue){
                $ResourceUsermapModel->update($resource['id'], ['quantity' => $maxValue, 'consumed_at' => null]);
            } else {
                $consumptionTime = $consumptionTime->addSeconds($restoratedValue * $restorationTime);
                $ResourceUsermapModel->update($resource['id'], ['quantity' => $newValue, 'consumed_at' => $consumptionTime->toDateTimeString()]);
            }
        }
    }

    public function getNextRestoration ($restorationTime, $consumptionTime)
    {
        if($consumptionTime){
            $consumptionTime = Time::parse($consumptionTime, Time::now()->getTimezone());
            $nextRestoration = $consumptionTime->addSeconds($restorationTime);
            return Time::now()->difference($nextRestoration)->getSeconds();
        } 
        return 0;
    }

    public function proccessItemCost ($cost_config)
    {
        $DescriptionModel = model('DescriptionModel');
        $resourceCodes = array_keys($cost_config);
        $resources = $this->whereIn('code', $resourceCodes)->get()->getResultArray();
        foreach($resources as &$resource){
            $resource = array_merge($resource, $DescriptionModel->getItem('resource', $resource['id']));
            $resource['quantity'] = $cost_config[$resource['code']];
        }
        return $resources;
    }

    public function substract ($user_id, $resources)
    {
        $ResourceUsermapModel = model('ResourceUsermapModel');
        $user_resources = $ResourceUsermapModel->where('user_id', $user_id)->get()->getResultArray();

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




    public function createUserItem($data)
    {
        $ResourceUsermapModel = model('ResourceUsermapModel');
        $resource = $this->where('code', $data['code'])->get()->getRowArray();
        $data = [
            'item_id' => $resource['id'],
            'user_id' => $data['user_id'],
            'quantity' => $data['quantity']
        ];
        $result = $ResourceUsermapModel->insert($data, true);
        return $result;  
    }

    public function createUserList ($user_id, $resources)
    {
        foreach($resources as $code => $quantity){
            $resource = $this->join('resources_usermap', 'resources_usermap.item_id = resources.id AND resources_usermap.user_id = '.$user_id)
            ->where('code', $code)->get()->getRowArray();
            if(isset($resource['id'])){
                $this->updateUserItem([
                    'code' => $code, 
                    'user_id' => $user_id, 
                    'quantity' => $quantity
                ]);
            } else {
                $this->createUserItem([
                    'code' => $code, 
                    'user_id' => $user_id, 
                    'quantity' => $quantity
                ]);
            }
        }
        return;        
    }
    
    public function updateUserItem($data)
    {
        $ResourceUsermapModel = model('ResourceUsermapModel');
        $resource = $this->where('code', $data['code'])->get()->getRowArray();
        $data = [
            'item_id' => $resource['id'],
            'user_id' => $data['user_id'],
            'quantity' => $data['quantity']
        ];
        $result = $ResourceUsermapModel->insert($data, true);
        return $result;  
    }
    

}