<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class SkillModel extends Model
{
    protected $table      = 'skills';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'code'
    ];
    
    protected $useTimestamps = false;
    private $config = [];

    public function getList ($data) 
    {
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('skills_usermap', 'skills_usermap.item_id = skills.id AND skills_usermap.user_id = '.$data['user_id'], 'left')
            ->select('skills.id, skills.code, skills.group_id, skills.icon, skills.cost_config, skills.level, skills.unblock_after, (skills_usermap.item_id IS NOT NULL) AS is_gained');
        }
        if(isset($data['limit']) && isset($data['offset'])){
            $this->limit($data['limit'], $data['offset']);
        }
        
        $skills = $this->get()->getResultArray();

        if(empty($skills)){
            return false;
        }
        $result = [];
        foreach($skills as &$skill){
            $skill['cost_config'] = json_decode($skill['cost_config'], true);
            $skill['is_gained'] = (bool) $skill['is_gained'];
            $skill = array_merge($skill, $DescriptionModel->getItem('skill', $skill['id']));
            if($data['user_id']){
                $skill['is_available'] = $this->checkAvailable($skill, $data['user_id']);
                $skill['is_purchasable'] = $this->checkPurchasable($skill, $data['user_id']);
            }
            //$skill['image'] = base_url('image/' . $skill['image']);
            //$skill['progress'] = $this->calculateProgress($skill);
            //$skill['params'] = json_decode($skill['params']);
        }
        $table = $this->drawTable($skills, 'level', 'code');
        $levelCount = 0;
        $prevCount = 0;
        $nextCount = 0;
        foreach(array_group_by($skills, ['group_id']) as $code => $levels){
            $skillRow = [
                'title' => lang('App.skills.itemClass.'.$code.'.title'),
                'list' => []
            ];
            foreach(array_group_by($levels, ['code']) as $level => $skillGroup){
                $skillRow['list'][] = [
                    'title' => $level,
                    'list' => $skillGroup
                ];
            }
            $result[] = $skillRow;
        }
        return $result;
    }


    private function drawTable($skills, $rowKey, $columnKey){
        $table = [];
        $rows = array_column($skills, null, $rowKey);
        $cols = array_column($skills, null, $columnKey);
        $rowKeys = array_keys($rows);
        $colKeys = array_keys($cols);
        for($i = 0; $i < count($rowKeys); $i++){
            $row = [];
            for($k = 0; $k < count($colKeys); $k++){
                $row[$k] = array_column($skills, null, $rowKey)[$colKeys[$k]];
            }
            $table[] = $row;
        }
        print_r($table);
        die;
    }
    
    private function searchInSkills($arr, $search){
        $result = [];
        foreach($arr as $item){
            
            foreach($arr as $item){

            }
        }
    }

    public function getItem ($code, $user_id, $item_id) 
    {
        $resource = $this->where('user_id', $user_id)->where('item_id', $item_id)->where('code', $code)->get()->getResultArray();
        return $resource;
    }
    public function checkAvailable ($skill, $user_id)
    {
        if(!(bool) $skill['unblock_after']) return true;
        return !empty($this->join('skills_usermap', 'skills_usermap.item_id = skills.id AND skills_usermap.user_id = '.$user_id)
        ->where('skills_usermap.item_id', $skill['unblock_after'])->get()->getRowArray());
    }
    public function checkPurchasable ($skill, $user_id)
    {
        if(empty($skill['cost_config'])) return true;
        $ResourceModel = model('ResourceModel');
        $resources = $ResourceModel->getList(['user_id' => $user_id]);
        foreach($skill['cost_config'] as $resourceTitle => $quantity){
            if($quantity > $resources[$resourceTitle]['quantity']){
                return false;
            }
        }
        return true;
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