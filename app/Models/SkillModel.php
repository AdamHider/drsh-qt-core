<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class SkillModel extends Model
{
    use PermissionTrait;
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
            ->select('skills.id, skills.code, skills.group_id, skills.chain, skills.image, skills.cost_config, skills.level, skills.unblock_after, (skills_usermap.item_id IS NOT NULL) AS is_gained');
        }
        if(isset($data['limit']) && isset($data['offset'])){
            $this->limit($data['limit'], $data['offset']);
        }
        
        $skills = $this->orderBy('code, level')->get()->getResultArray();

        if(empty($skills)) return false;
        
        foreach($skills as &$skill){
            $cost_config = json_decode($skill['cost_config'], true);
            $skill['is_gained'] = (bool) $skill['is_gained'];
            $skill = array_merge($skill, $DescriptionModel->getItem('skill', $skill['id']));
            $skill['image'] = base_url('image/' . $skill['image']);
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
            unset($skill['cost_config']);
        }
        
        return $this->compileList($skills);
    }
    
    private function compileList($skills)
    {
        $DescriptionModel = model('DescriptionModel');
        $SkillGroupModel = model('SkillGroupModel');
        $result = [];
        foreach(array_group_by($skills, ['group_id']) as $category => $categories){
            $group = $SkillGroupModel->where('id', $category)->get()->getRowArray();
            $categoryObject = [
                'id' => $category,
                'color' => $group['color'],
                'available_total' => count(array_filter($categories, function($k) {return $k['is_purchasable'] == 1 && $k['is_available'] == 1; })),
                'list' => []
            ];
            $categoryObject = array_merge($categoryObject, $DescriptionModel->getItem('skill_group', $category));
            
            foreach(array_group_by($categories, ['code']) as $subcategory => $subcategories){
                $subcategoryObject = [
                    'list' => $this->buildColumns($subcategories),
                    'total' => count($subcategories),
                    'gained_total' => count(array_filter( $subcategories, function($skill) { return $skill['is_gained']; } ))
                ];
                $subcategoryObject = array_merge($subcategoryObject, $DescriptionModel->getItem('skill_subcategory', $subcategory));
                $categoryObject['list'][] = $subcategoryObject;
            }
            $result[] = $categoryObject;
        }
        return $result;
    }

    private function buildColumns($skills)
    {
        $result = [];
        $columns = array_group_by($skills, ['level']);
        foreach($columns as $index => &$column){
            $relations = [];
            if(isset($columns[$index+1])){ 
                $nextColumn = $columns[$index+1];
                foreach($column as $slotIndex => $slot){
                    foreach($nextColumn as $nextSlotIndex => $nextSlot){
                        if(in_array($slot['id'], explode(',', $nextSlot['unblock_after']))){
                            $relations[] = [
                                'direction' => count($column).'-'.$slotIndex.'-'.$nextSlotIndex.'-'.count($nextColumn),
                                'is_gained' => $slot['is_gained']
                            ];
                        }
                    }
                }
            }
            $result[] = [
                'slots' => $column,
                'relations' => $relations
            ];
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
        ->whereIn('skills_usermap.item_id', explode(',', $skill['unblock_after']))->get()->getRowArray());
    }
    public function checkPurchasable ($cost_config, $user_id)
    {
        if(empty($cost_config)) return true;
        $ResourceModel = model('ResourceModel');
        $resources = $ResourceModel->getList(['user_id' => $user_id]);
        foreach($cost_config as $resourceTitle => $quantity){
            if(isset($resources[$resourceTitle]) && $quantity > $resources[$resourceTitle]['quantity']){
                return false;
            }
        }
        return true;
    }

    
    public function claimItem($skill_id, $user_id)
    {
        $ResourceModel = model('ResourceModel');
        $SkillUsermapModel = model('SkillUsermapModel');
        $UserSettingsModel = model('UserSettingsModel');

        $skill = $this->join('skills_usermap', 'skills_usermap.item_id = skills.id AND skills_usermap.user_id = '.$user_id, 'left')
        ->where('id', $skill_id)->get()->getRowArray();

        if(empty($skill)){
            return 'not_found';
        }
        $skill['is_gained'] = (bool) $skill['item_id'];
        $cost_config = json_decode($skill['cost_config'], true);

        if($this->checkAvailable($skill, $user_id) && $this->checkPurchasable($cost_config, $user_id)){
            if($ResourceModel->substract($user_id, $cost_config)){
                $UserSettingsModel->updateItem(['user_id' => $user_id, 'code' => $skill['target'], 'value' => $skill['value']]);
                $SkillUsermapModel->insert(['item_id' => $skill['id'], 'user_id' => $user_id], true);
                return 'success';
            };
            return 'forbidden';
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