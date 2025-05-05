<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Events\Events;

class SkillModel extends Model
{
    use ResourceTrait;
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
        $SettingsModel = model('SettingsModel');
        
        if($data['user_id']){
            $this->join('skills_usermap', 'skills_usermap.item_id = skills.id AND skills_usermap.user_id = '.$data['user_id'], 'left')
            ->select('skills.id, skills.code, skills.group_id, skills.chain, skills.image, skills_usermap.cost_config, skills.level, skills.modifiers_config, skills.unblock_after, (skills_usermap.status = "is_gained") AS is_gained');
        }
        if(isset($data['limit']) && isset($data['offset'])){
            $this->limit($data['limit'], $data['offset']);
        }
        
        $skills = $this->orderBy('code, level, chain')->get()->getResultArray();

        if(empty($skills)) return false;
        
        foreach($skills as &$skill){
            $cost_config = json_decode($skill['cost_config'], true);
            $skill['is_gained'] = (bool) $skill['is_gained'];
            $skill = array_merge($skill, $DescriptionModel->getItem('skill', $skill['id']));
            $skill['image'] = base_url('image/index.php'.$skill['image']);
            if($data['user_id']){
                $skill['is_available'] = $this->checkAvailable($skill, $data['user_id']);
                $skill['is_purchasable'] = $this->checkPurchasable($cost_config, $data['user_id']);
            }
            if($skill['is_available'] && !empty($cost_config)){
                $skill['cost'] = $ResourceModel->proccessItemCost($cost_config);
            }
            if(!$skill['is_available'] && !$skill['is_gained']){
                $skill['required_skills'] = array_filter($skills, function ($item) use ($skill)  {
                    return in_array($item['id'], explode(',', $skill['unblock_after'])); 
                });
            }
            if(!empty($skill['modifiers_config'])){
                $modifiers_config = json_decode($skill['modifiers_config'], true);
                $skill['modifiers'] = $SettingsModel->processModifierList($modifiers_config);
                unset($skill['modifiers_config']);
            }
            unset($skill['cost_config']);
        }
        
        return $this->compileList($skills);
    }
    private function compileList($skills)
    {
        $DescriptionModel = model('DescriptionModel');
        $result = [];
        foreach(array_group_by($skills, ['code']) as $category => $categories){
            $categoryObject = [
                'list' => $this->buildColumns($categories),
                'total' => count($categories),
                'gained_total' => count(array_filter( $categories, function($skill) { return $skill['is_gained'] == 1; } ))
            ];
            $categoryObject = array_merge($categoryObject, $DescriptionModel->getItem('skill_subcategory', $category));
            $result[] = $categoryObject;
        }
        return $result;
    }

    private function buildColumns($skills)
    {
        $result = [];
        $columns = array_group_by($skills, ['level']);
        foreach($columns as $index => $column){
            $relations = [];
            if(isset($columns[$index+1])){ 
                $nextColumn = $columns[$index+1];
                $relations = $this->buildRelations($column, $nextColumn);
            }
            $result[] = [
                'slots' => $column,
                'relations' => $relations
            ];
        }
        return $result;
    }
    
    private function buildRelations($column, $nextColumn)
    {
        $result = [];
        foreach($column as $slotIndex => $slot){
            foreach($nextColumn as $nextSlotIndex => $nextSlot){
                if(in_array($slot['id'], explode(',', $nextSlot['unblock_after']))){
                    $result[] = [
                        'direction' => count($column).'-'.$slotIndex.'-'.$nextSlotIndex.'-'.count($nextColumn),
                        'is_gained' => $slot['is_gained']
                    ];
                }
            }
        }
        return $result;
    }

    public function getItem ($code, $user_id, $item_id) 
    {
        $resource = $this->where('user_id', $user_id)->where('item_id', $item_id)->where('code', $code)->get()->getResultArray();
        return $resource;
    }
    public function checkAvailable ($skill)
    {
        return !empty($this->join('skills_usermap', 'skills_usermap.item_id = skills.id AND skills_usermap.user_id = '.session()->get('user_id').' AND skills_usermap.status = "created"')
        ->where('skills_usermap.item_id', $skill['id'])->get()->getRowArray());
    }
    public function checkPurchasable ($cost_config)
    {
        if(empty($cost_config)) return true;
        $ResourceModel = model('ResourceModel');
        $resources = $ResourceModel->getList(['user_id' => session()->get('user_id')]);
        foreach($cost_config as $resourceTitle => $quantity){
            if(isset($resources[$resourceTitle]) && $quantity > $resources[$resourceTitle]['quantity']){
                return false;
            }
        }
        return true;
    }

    
    public function claimItem($skill_id)
    {
        $ResourceModel = model('ResourceModel');
        $SettingsModel = model('SettingsModel');

        $skill = $this->join('skills_usermap', 'skills_usermap.item_id = skills.id AND skills_usermap.user_id = '.session()->get('user_id'), 'left')
        ->select('skills.*, skills_usermap.cost_config as cost_recalculated')->where('id', $skill_id)->get()->getRowArray();

        if(empty($skill)){
            return 'not_found';
        }
        $cost_config = json_decode($skill['cost_recalculated'], true);
        
        $modifiers_config = json_decode($skill['modifiers_config'], true);

        if($this->checkAvailable($skill) && $this->checkPurchasable($cost_config)){
            if($ResourceModel->enrollUserList(session()->get('user_id'), $cost_config, 'substract')){
                $SettingsModel->createModifierList($modifiers_config);
                $this->updateUserItem(['item_id' => $skill['id'], 'user_id' => session()->get('user_id'), 'status' => 'gained']);
                Events::trigger('skillGained', $skill['id']);
                return 'success';
            };
            return 'forbidden';
        } else {
            return 'forbidden';
        }
    }
    public function linkItem($skill_id, $user_id, $mode = 'exact') 
    {
        $SkillUsermapModel = model('SkillUsermapModel');
        $field = 'id';
        if($mode == 'next'){
            $field = 'unblock_after';
        } 
        $skills = $this->where($field, $skill_id)->get()->getResultArray();
        if(!empty($skills)){
            foreach($skills as $skill){
                $data = [
                    'item_id' => $skill['id'],
                    'user_id' => $user_id,
                    'status' => 'created'
                ];
                $cost_config = json_decode($skill['cost_config'], true);
                $data['cost_config'] = json_encode($this->recalculateResources($cost_config));
                $SkillUsermapModel->insert($data, true);
            }
        }
    }
    public function updateUserItem($data)
    {
        $SkillUsermapModel = model('SkillUsermapModel');
        return $SkillUsermapModel->set('status', $data['status'], null)->where(['item_id' => $data['item_id'], 'user_id' => session()->get('user_id')])->update();
    }
}