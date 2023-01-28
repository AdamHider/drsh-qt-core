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

    private $usernameSamples = [
        'misiq', 'arslan', 'qaplan'
    ];
    private $usernameAffixes = [
        'qirim',
        'qirimli',
        'qirimtatar'
    ];    

    public function getItem ($user_id) 
    {
        if ($user_id == 0) {
            return $this->getGuestItem();
        }
        $user = $this->where('id', $user_id)->get()->getRow();
        
        if(!$user){
            return 'not_found';
        }
        unset($user->password);
        return $user;
    }

    public function getList () 
    {
        return $this->findAll();
    }
        
    public function itemCreate ($data)
    {
        if (empty($data['username'])) {
            $data['username'] = $this->generateUsername();
        }
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
    
    private function generateUsername()
    {
        $usernamePrefix = $this->usernameSamples[array_rand($this->usernameSamples)];
        $affix = $this->getUsernameAffix($usernamePrefix);
        $result = $usernamePrefix.$affix;
        return $result;
        
    }
    public function checkUsername($username)
    {
        $user = $this->where('username', $username)->get()->getRow();
        return $user && $user->username;
    }
    public function getUsernameSuggestions($username)
    {
        $result = [];
        $result[] = $username.substr(time(), -3);
        foreach($this->usernameAffixes as $affix){
            if(!$this->checkUsername($username.'_'.$affix)){
                $result[] = $username.'_'.$affix;
            } else {
                $result[] = $username.substr(time(), -3);
            }
        }
        return $result;
    }
    private function getUsernameAffix($username)
    {
        $lastUsername = $this->like('username', $username, 'after')->selectMax('id')->get()->getRow();
        if ($lastUsername->id > 0) {
            return $lastUsername->id++;
        }
        return '';
    }
    public function checkEmail($email)
    {
        $user = $this->where('email', $email)->get()->getRow();
        return $user->email;
    }
    
    public function getActiveItem(){
        return $this->getItem( session()->get('user_id') );
    }
    
    private function getGuestItem(){
        return (object)[
            'id'=> 0,
            'username'=>'Guest'
        ];
    }
    protected function hashPassword (array $data)
    {
        if ( isset($data['data']['password']) ){
            $data['data']['password'] = password_hash($data['data']['password'],PASSWORD_BCRYPT);
        }
        return $data;
    }

}