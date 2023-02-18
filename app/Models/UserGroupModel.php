<?php

namespace App\Models;

use CodeIgniter\Model;

class UserClassroomModel extends Model
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
    public function getList ($user_id) 
    {
        $DescriptionModel = model('DescriptionModel');

        $groups =  $this->join('users_to_user_groups', 'users_to_user_groups.item_id = user_groups.id')
        ->select('user_groups.id, user_groups.code, user_groups.path')
        ->where('users_to_user_groups.user_id', $user_id)->get()->getResultArray();

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