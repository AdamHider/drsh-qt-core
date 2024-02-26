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

        if(empty($skills)) return false;
        
        foreach($skills as &$skill){
            $skill['cost_config'] = json_decode($skill['cost_config'], true);
            $skill['is_gained'] = (bool) $skill['is_gained'];
            $skill = array_merge($skill, $DescriptionModel->getItem('skill', $skill['id']));
            if($data['user_id']){
                $skill['is_available'] = $this->checkAvailable($skill, $data['user_id']);
                $skill['is_purchasable'] = $this->checkPurchasable($skill, $data['user_id']);
                $skill['prev_relation'] = $this->checkPrevRelation($skill, $skills);
            }
        }
        
        $result = [];
        foreach(array_group_by($skills, ['group_id']) as $code => $groups){
            $skillRow = [
                'title' => lang('App.skills.itemClass.'.$code.'.title'),
                'table' =>  $this->drawTable($groups, 'level', 'code')
            ];
            $result[] = $skillRow;
        }
        return $result;
    }


    private function drawTable($skills, $rowKey, $columnKey){
        $table = $this->drawTableMask($skills, $rowKey, $columnKey);
        
        foreach($skills as $skill){
            $table[$skill[$rowKey]][$skill[$columnKey]] = $skill;
        }
        return $table;
    }

    private function drawTableMask($skills, $rowKey, $columnKey){
        $mask = [];
        $rowKeys = max(array_keys(array_column($skills, null, $rowKey)));
        $colKeys = array_keys(array_column($skills, null, $columnKey));
        for($i = 1; $i <= $rowKeys; $i++){
            $row = [];
            for($k = 0; $k < count($colKeys); $k++){
                $row[$colKeys[$k]] = [];
            }
            $mask[$i] = $row;
        }
        return $mask;
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
    public function checkPrevRelation ($skill, $skills)
    {
        if(!empty($skill['unblock_after'])){
            $previousSkill = array_column($skills, null, 'id')[$skill['unblock_after']];
            if($previousSkill['code'] == $skill['code'] && $skill['level'] - $previousSkill['level'] == 1) return 1;
        };
        return 0;
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