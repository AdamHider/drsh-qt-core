<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'username', 
        'password', 
        'email', 
        'phone', 
        'blocked'
    ];
    
    protected $validationRules    = [
        'username'     => [
            'label' =>'username',
            'rules' =>'required|min_length[3]|is_unique[users.username]',
            'errors'=>[
                'required'=>'required',
                'min_length'=>'short',
                'is_unique'=>'notunique'
            ]
        ],
        'password'     => [
            'label' =>'password',
            'rules' =>'required|min_length[4]',
            'errors'=>[
                'required'=>'required',
                'min_length'=>'short'
            ]
        ],
        'password_confirm'     => [
            'label' =>'password',
            'rules' =>'required_with[password]|matches[password]',
            'errors'=>[
                'required_with'=>'required',
                'matches'=>'notmatches'
            ]
        ],
        'email'    => [
            //'label' =>'user_email',
            'rules' =>'permit_empty|valid_email|is_unique[users.email]',
            'errors'=>[
                'valid_email'=>'invalid',
                'is_unique'=>'notunique'
            ]
        ],
        'phone'    => [
            //'label' =>'user_phone',
            'rules' =>'permit_empty|numeric|exact_length[11]|is_unique[users.phone]',
            'errors'=>[
                'numeric'=>'invalid',
                'exact_length'=>'short',
                'is_unique'=>'notunique'
            ]
        ]
    ];

    protected $useTimestamps = false;
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];


    public function getItem ($id) 
    {
        $user = $this->where('id', $id)->get()->getRow();
        
        unset($user->password);
        return $user;
    }

    public function getList () 
    {
        return $this->findAll();
    }
        
    public function itemCreate ($data)
    {
        $this->transBegin();

        $user_id = $this->insert($data, true);
        /*
        if( $user_id ){
            $UserGroupMemberModel=model('UserGroupMemberModel');
            $UserGroupMemberModel->tableSet('user_group_member_list');
            $UserGroupMemberModel->joinGroupByType($user_id,'customer');
            $this->allowedFields[]='owner_id';
            $this->update($user_id,['owner_id'=>$user_id]);
        }*/

        $this->transCommit();

        return $user_id;        
    }

    public function signIn ($username, $password)
    {
        $user = $this->where('username', $username)->get()->getRow();
        if(!$user || !$user->id){
            return 'not_found';
        }
        if(!password_verify($password, $user->password)){
            return 'wrong_password';
        }
        if($user->blocked){
            return 'blocked';
        }
        if($user->deleted_at){
            return 'is_deleted';
        }
        /*
        $PermissionModel=model('PermissionModel');
        $PermissionModel->listFillSession();
        $this->protect(false)
                ->update($user->id,['signed_in_at'=>\CodeIgniter\I18n\Time::now()]);
        $this->protect(true);*/
        session()->set('user_id', $user->id);
        return 'success' ;
    }
    
    public function getActiveItem(){
        return $this->getItem( session()->get('user_id') );
    }
    
    protected function hashPassword (array $data)
    {
        if ( isset($data['data']['password']) ){
            $data['data']['password'] = password_hash($data['data']['password'],PASSWORD_BCRYPT);
        }
        return $data;
    }

}