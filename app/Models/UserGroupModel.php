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

    public function getList () 
    {
        $DescriptionModel = model('DescriptionModel');

        $groups =  $this->join('user_groups_usermap', 'user_groups_usermap.item_id = user_groups.id')
        ->select('user_groups.id, user_groups.code, user_groups.path')
        ->where('user_groups_usermap.user_id', session()->get('user_id'))->get()->getResultArray();

        foreach($groups as &$group){
            $group['description'] = $DescriptionModel->getItem('user_group', $group['id']);
        }
        return $groups;
    }
        
    public function createUserItem ($user_id, $code)
    {
        $UserGroupUsermapModel = model('UserGroupUsermapModel');
        $user_group = $this->where('code', $code)->get()->getRowArray();
        $this->transBegin();
        $result = $UserGroupUsermapModel->insert(['item_id' => $user_group['id'], 'user_id' => $user_id], true);
        $this->transCommit();
        return;        
    }

}