<?php
namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model{
    
    use PermissionTrait;
    
    protected $table      = 'permisions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_group_id',
        'scope',
        'method',
        'owner',
        'ally',
        'other'
    ];
    
    public function itemGet(){
        return $this->get()->getResult();
    }
    public function listGet(){
        $this->listFillSession();
        if( $this->isAdmin() ){
            return $this->get()->getResult();
        }
        return [];
    }
    
    public function updateSession(){
        $UserGroupModel = model('UserGroupModel');
        $max_user_group = $UserGroupModel->join('users_to_user_groups', 'users_to_user_groups.item_id = user_groups.id')
        ->select('MAX(user_groups.id) as id')->where('users_to_user_groups.user_id', session()->get('user_id'))->get()->getRow('id');

        $permissions = $this->where('user_group_id', $max_user_group)->get()->getResult();
        $result = [];
        foreach($permissions as $permission){
            $result["{$permission->scope}.{$permission->method}"]=[
                'owner' => $permission->owner,
                'party' => $permission->party,
                'other' => $permission->other,
            ];
        }
        session()->set('permissions', $result);
    }
    
    public function createItem($permited_owner,$permited_class,$permited_method,$permited_rights){
        if( !sudo() ){
            return false;
        }
        $permission_id=$this
                ->where('permited_class',$permited_class)
                ->where('permited_method',$permited_method)->get()->getRow('permission_id');
        if( !$permission_id ){
            $data=[
                'permited_class'=>$permited_class,
                'permited_method'=>$permited_method
            ];
            $permission_id=$this->insert($data);
        }
        return $this->update($permission_id,[$permited_owner=>$permited_rights]);
    }
    
    
}