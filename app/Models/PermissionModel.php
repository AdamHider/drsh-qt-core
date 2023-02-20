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
        $permissions = $this->whereIn('user_group_id', session()->get('user_data')['group_ids'])
        ->select("scope, method, status, GROUP_CONCAT(owner) as owner, GROUP_CONCAT(shared) as shared, GROUP_CONCAT(other) as other")->groupBy('scope, status')
        ->get()->getResultArray();
        $result = [];
        foreach($permissions as $permission){
            $permission_name = $permission['scope'].".".$permission['method'];
            if(empty($result[$permission_name])){
                $result[$permission_name] = [];
            }
            $result[$permission_name][$permission['status']] = [
                'owner' => $permission['owner'],
                'shared' => $permission['shared'],
                'other' => $permission['other'],
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