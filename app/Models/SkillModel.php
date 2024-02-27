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
        $ResourceModel = model('ResourceModel');
        
        if($data['user_id']){
            $this->join('skills_usermap', 'skills_usermap.item_id = skills.id AND skills_usermap.user_id = '.$data['user_id'], 'left')
            ->select('skills.id, skills.code, skills.group_id, skills.chain, skills.icon, skills.cost_config, skills.level, skills.unblock_after, (skills_usermap.item_id IS NOT NULL) AS is_gained');
        }
        if(isset($data['limit']) && isset($data['offset'])){
            $this->limit($data['limit'], $data['offset']);
        }
        
        $skills = $this->get()->getResultArray();

        if(empty($skills)) return false;
        
        foreach($skills as &$skill){
            $cost_config = json_decode($skill['cost_config'], true);
            $skill['is_gained'] = (bool) $skill['is_gained'];
            $skill = array_merge($skill, $DescriptionModel->getItem('skill', $skill['id']));
            if($data['user_id']){
                $skill['is_available'] = $this->checkAvailable($skill, $data['user_id']);
                $skill['is_purchasable'] = $this->checkPurchasable($cost_config, $data['user_id']);
            }
            if($skill['is_available'] && !empty($cost_config)){
                $skill['cost'] = $ResourceModel->proccessItemCost($cost_config);
            }
            if(!$skill['is_available'] && !$skill['is_gained']){
                $skill['required_skill'] = array_column($skills, null, 'id')[$skill['unblock_after']];
            }
            unset($skill['cost_config']);
        }
        
        $result = [];
        foreach(array_group_by($skills, ['group_id']) as $category => $categories){
            $categoryObject = [
                'id' => $category,
                'list' => []
            ];
            $categoryObject = array_merge($categoryObject, $DescriptionModel->getItem('skill_group', $category));

            foreach(array_group_by($categories, ['code']) as $subcategory => $subcategories){
                $subcategoryObject = [
                    'list' => []
                ];
                foreach(array_group_by($subcategories, ['chain']) as $chain => $chains){
                    $subcategoryObject['list'][] = $chains;
                }
                $categoryObject['list'][] = $subcategoryObject;
            }
            $result[] = $categoryObject;
        }
        return $result;
    }
    
    public function getItem ($code, $user_id, $item_id) 
    {
        $resource = $this->where('user_id', $user_id)->where('item_id', $item_id)->where('code', $code)->get()->getResultArray();
        return $resource;
    }
    public function checkAvailable ($skill, $user_id)
    {
        if((bool) $skill['is_gained']) return false;
        if(!(bool) $skill['unblock_after']) return true;
        return !empty($this->join('skills_usermap', 'skills_usermap.item_id = skills.id AND skills_usermap.user_id = '.$user_id)
        ->where('skills_usermap.item_id', $skill['unblock_after'])->get()->getRowArray());
    }
    public function checkPurchasable ($cost_config, $user_id)
    {
        if(empty($cost_config)) return true;
        $ResourceModel = model('ResourceModel');
        $resources = $ResourceModel->getList(['user_id' => $user_id]);
        foreach($cost_config as $resourceTitle => $quantity){
            if($quantity > $resources[$resourceTitle]['quantity']){
                return false;
            }
        }
        return true;
    }

    
    public function claimItem($skill_id, $user_id)
    {
        $ResourceModel = model('ResourceModel');

        if(!$this->hasPermission($skill_id, 'r')){
            return 'forbidden';
        }
        
        $skill = $this->where('id', $skill_id)->get()->getRowArray();

        if(empty($quest)){
            return 'not_found';
        }
        
        $cost_config = json_decode($skill['cost_config'], true);
        if((bool) $skill['is_gained']){
            return 'forbidden';
        }
        if($this->checkAvailable($skill, $user_id) && $this->checkPurchasable($cost_config, $user_id)){
            $UserResourcesExpensesModel = model('UserResourcesExpensesModel');
            $data = [
                'user_id' => session()->get('user_id'),
                'code' => $resource_title,
                'item_code' => 'quest',
                'item_id' => $quest['id'],
                'quantity' => $resource_quantity
            ];
            $UserResourcesExpensesModel->createItem($data);
            return true;
        } else {
            return 'forbidden';
        }
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