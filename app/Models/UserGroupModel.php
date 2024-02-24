<?php

namespace App\Models;

use CodeIgniter\Model;

class UserGroupModel extends Model
{
    protected $table      = 'user_groups';
    protected $primaryKey = 'user_id';

    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'user_id', 
        'classroom_id'
    ];
    
    protected $useTimestamps = false;

    public function getItem ($user_id, $classroom_id) 
    {
        $user_classroom = $this->where('user_id = '.$user_id.' AND classroom_id = '.$classroom_id)->get()->getRowArray(0);
        return $user_classroom;
    }
    public function getList ($user_id = false) 
    {
        $DescriptionModel = model('DescriptionModel');
        if(!$user_id){
            $user_id == session()->get('user_id');
        }
        $groups =  $this->join('user_groups_usermap', 'user_groups_usermap.item_id = user_groups.id')
        ->select('user_groups.id, user_groups.code, user_groups.path')
        ->where('user_groups_usermap.user_id', $user_id)->get()->getResultArray();

        foreach($groups as &$group){
            $group['description'] = $DescriptionModel->getItem('user_group', $group['id']);
        }
        return $groups;
    }
        
    public function itemCreate ($user_id, $classroom_id)
    {
        $this->transBegin();
        $data = [
            'user_id'       => $user_id,
            'classroom_id'  => $classroom_id
            
        ];
        $this->insert($data, true);
        
        $this->transCommit();
        return;        
    }



}