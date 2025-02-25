<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Events\Events;

class UserModel extends Model
{
    use PermissionTrait;
    protected $table      = 'users';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'name',
        'username', 
        'password', 
        'auth_key',
        'email', 
        'phone', 
        'gender',
        'blocked'
    ];
    
    protected $validationRules    = [
        'name'     => [
            'label' =>'name',
            'rules' =>'required|min_length[3]',
            'errors'=>[
                'required'=>'required',
                'min_length'=>'short',
                'is_unique'=>'notunique'
            ]
        ],
        'username'     => [
            'label' =>'username',
            'rules' =>'required|min_length[3]',
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
        'auth_key'     => [
            'label' =>'password',
            'rules' =>'required',
            'errors'=>[
                'required'=>'required'
            ]
        ],
        'email'    => [
            'rules' =>'permit_empty|valid_email',
            'errors'=>[
                'valid_email'=>'invalid',
                'is_unique'=>'notunique'
            ]
        ],
        'phone'    => [
            'rules' =>'permit_empty|numeric|exact_length[11]',
            'errors'=>[
                'numeric'=>'invalid',
                'exact_length'=>'short',
                'is_unique'=>'notunique'
            ]
        ]
    ];

    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

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
        if(!$this->hasPermission($user_id, 'r')){
            return 'forbidden';
        }
        $user = $this->where('id', $user_id)->get()->getRowArray();
        
        if(!$user){
            return 'not_found';
        }
        $SettingsModel = model('SettingsModel');
        $user['settings'] = $SettingsModel->getList(['user_id' => $user['id']]);

        $UserGroupModel = model('UserGroupModel');
        $user['groups'] = $UserGroupModel->getList(['user_id' => $user['id']]);
        $user['group_ids'] = [];
        foreach($user['groups'] as $group){
            $user['group_ids'][] = $group['id'];
        } 
        
        $CharacterModel = model('CharacterModel');
        $user['character'] = $CharacterModel->getItem($user['settings']['characterId']['value']);
        
        $UserLevelModel = model('UserLevelModel');
        $user['level'] = $UserLevelModel->getCurrentItem();

        $SettingsModifiersModel = model('SettingsModifiersModel');
        $user['modifiers'] = $SettingsModifiersModel->getList();
        
        $ResourceModel = model('ResourceModel');
        $user['resources'] = $ResourceModel->getList(['user_id' => $user['id']]);

        $user['statistics'] = $this->getItemStatistics($user['id']);
        
        unset($user['password']);
        unset($user['auth_key']);
        return $user;
    }
    public function updateItem ($user_id, $data)
    {
        $this->transBegin();
        $this->update(['id' => $user_id], $data);

        $this->transCommit();

        return true;        
    }
    public function createItem ($data)
    {
        if (empty($data['username'])) {
            $data['username'] = $this->generateUsername($data['name']);
        }

        $this->transBegin();
        $data['auth_key'] = '####';
        $user_id = $this->insert($data, true);
        
        $auth_key = md5($user_id.$data['password']);
        $ok = $this->update(['id' => $user_id], ['auth_key' => $auth_key]);
    
        $this->transCommit();

        Events::trigger('signUp', $user_id);

        return $auth_key;        
    }

    public function signIn ($auth_key)
    {
        $user = $this->where('auth_key', $auth_key)->get()->getRowArray();

        if(!$user || !$user['id']){
            return 'not_found';
        }
        if($user['blocked']){
            return 'blocked';
        }
        if($user['deleted_at']){
            return 'is_deleted';
        }
        session()->set('user_id', $user['id']);
        return 'success' ;
    }
    public function saveItemPassword($data, $user_id)
    {
        $user = $this->where('id', $user_id)->get()->getRowArray();

        //CHECK OLD PASSWORD SECTION
        if (!password_verify($data['old_password'], $user['password'])) {
            return 'wrong_password';
        }
        //CHECK PASSWORD SECTION
        if (empty($data['password'])) {
            return 'empty_password';
        }
        if ($data['password'] !== $data['password_confirm']) {
            return 'different_password';
        }
        return $this->set('password', $data['password'])->where('id', $user_id)->update();
    }

    public function updateItemAuthKey($user_id)
    {
        $user = $this->where('id', $user_id)->get()->getRowArray();
        $auth_key = md5($user['id'].$user['password']);
        $this->set('auth_key', $auth_key)->where('id', $user_id)->update();
        return $auth_key;
    }
    
    public function generateUsername($name)
    {
        if(empty($name)) return 'not_found';
        $name = str_replace(' ', '\_', $name);
        $prefix = convert_accented_characters($name);
        $affix = $this->getUsernameAffix($prefix);
        $result = strtolower($prefix.$affix);
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
        $lastUsername = $this->where('username REGEXP "'.$username.'[0-9]+" OR username = "'.$username.'"')->select('MAX(id) as id')->get()->getRow();
        if ($lastUsername->id > 0) {
            return $lastUsername->id++.rand(10, 99);
        }
        return '';
    }
    public function checkEmail($email)
    {
        $user = $this->where('email', $email)->get()->getRow();
        if(empty($user->email)){
            return false;
        }
        return true;
    }
    
    public function getActiveItem(){
        return $this->getItem( session()->get('user_id') );
    }
    
    private function getGuestItem(){
        return [
            'id'=> 0,
            'username'=>'Guest'
        ];
    }
    protected function hashPassword (array $data)
    {
        if ( isset($data['data']['password']) ){
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_BCRYPT);
        }
        return $data;
    }
    public function getItemAuth($username, $password){
        
        $user = $this->where('username', $username)->get()->getRowArray();
        if(!password_verify($password, $user['password'])){
            return 'wrong_password';
        }
        if(!$user || !$user['id']){
            return 'not_found';
        }
        if($user['blocked']){
            return 'blocked';
        }
        if($user['deleted_at']){
            return 'is_deleted';
        }
        return $user['auth_key'];
    }
    public function getItemStatistics($user_id )
    {
        $SkillUsermapModel = model('SkillUsermapModel');
        $ExerciseModel = model('ExerciseModel');

        $user_statistics = $ExerciseModel->where('user_id', $user_id)
        ->select("COALESCE(sum(points), 0) as total_points, COALESCE(COUNT(points), 0) as total_exercises")
        ->get()->getRowArray();

        $dashboard = [
            'total_points' => [
                'label' => lang('App.user.statistics.total_points'),
                'value' => (int) $user_statistics['total_points']
            ],
            'total_exercises' => [
                'label' => lang('App.user.statistics.total_exercises'),
                'value' => (int) $user_statistics['total_exercises']
            ],
            'total_skills' =>  [
                'label' => lang('App.user.statistics.total_skills'),
                'value' => count($SkillUsermapModel->where('user_id', $user_id)->get()->getResultArray())
            ]
        ];

        return $dashboard;
    }

}