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
        $DescriptionModel = model('DescriptionModel');
        $resources = $this->select('resources.*, COALESCE(resources_usermap.quantity, 0) quantity, resources_usermap.consumed_at')
        ->join('resources_usermap', 'resources_usermap.item_id = resources.id AND resources_usermap.user_id = '.$data['user_id'], 'left')->get()->getResultArray();
        
        $result = [];
        
        foreach($resources as &$resource){
            $resource = array_merge($resource, $DescriptionModel->getItem('resource', $resource['id']));
            $result[$resource['code']] = [
                'quantity'      => $resource['quantity'],
                'icon'      => $resource['icon'],
                'title'      => $resource['title'],
                'description'      => $resource['description'],
                'color'      => $resource['color'],
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
        $resources = $this->whereIn('code', array_keys($cost_config))->get()->getResultArray();
        foreach($resources as &$resource){
            $resource = array_merge($resource, $DescriptionModel->getItem('resource', $resource['id']));
            $resource['quantity'] = $cost_config[$resource['code']];
        }
        return $resources;
    }

    public function substract ($user_id, $resources)
    {
        foreach($resources as $code => &$quantity){
            $quantity = $quantity * -1;
        }
        if(!$this->checkListQuantity($user_id, $resources)) return false;
        return $this->saveUserList($user_id, $resources);
    }

    public function checkListQuantity($user_id, $resources)
    {
        $list = $this->join('resources_usermap', 'resources_usermap.item_id = resources.id AND resources_usermap.user_id = '.$user_id)
        ->whereIn('code', array_keys($resources))->get()->getResultArray();
        foreach($list as &$item){
            if(($item['quantity'] + $resources[$item['code']]) < 0) return false;
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

    public function saveUserList ($user_id, $resources)
    {
        
        foreach($resources as $code => $quantity){
            $resource = $this->join('resources_usermap', 'resources_usermap.item_id = resources.id AND resources_usermap.user_id = '.$user_id)
            ->where('code', $code)->get()->getRowArray();
            if(isset($resource['id'])){
                if(!$this->updateUserItem([
                    'code' => $code, 
                    'user_id' => $user_id, 
                    'quantity' => $quantity
                ])){ 
                    return false;
                };
            } else {
                if(!$this->createUserItem([
                    'code' => $code, 
                    'user_id' => $user_id, 
                    'quantity' => $quantity
                ])){
                    return false;
                };
            }
        }
        return true;        
    }
    
    public function updateUserItem($data)
    {
        $ResourceUsermapModel = model('ResourceUsermapModel');
        $resource = $this->join('resources_usermap', 'resources_usermap.item_id = resources.id AND resources_usermap.user_id = '.$data['user_id'], 'left')
        ->where('code', $data['code'])->get()->getRowArray();
        $ResourceUsermapModel->set('quantity', 'quantity+'.$data['quantity'], false);
        if($resource['quantity'] < 0){
            $ResourceUsermapModel->set('consumed_at', Time::now()->toDateTimeString(), false);
        }
        return $ResourceUsermapModel->where(['item_id' => $resource['id'], 'user_id' => $data['user_id']])->update(); 
    }
    

}