<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class User extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $UserModel = model('UserModel');

        $user_id = $this->request->getVar('user_id');
        $session_user = false;
        if( !$user_id ){
            $session_user = true;
            $user_id = session()->get('user_id');
        }

        $user = $UserModel->getItem($user_id);

        if ($user == 'not_found') {
            return $this->failNotFound('not_found');
        }
        if ($session_user) {
            session()->set('user_data', $user);
        }
        return $this->respond($user);
    }
    public function getList()
    {
        $UserModel = model('UserModel');
        $result = $UserModel->getList();
        return $this->respond($result, 200);
    }
    public function saveItem()
    {
        $UserModel = model('UserModel');

        $username = $this->request->getVar('username');
        $email    = $this->request->getVar('email');
        $phone    = $this->request->getVar('phone');
        
        if(!$username){
            $username = session()->get('user_data')['username'];
        }
        if(!$email){
            $email = session()->get('user_data')['email'];
        }
        if(!$phone){
            $phone = session()->get('user_data')['phone'];
        }
        $data = [
            'username'  => $username,
            'email'     => $email,
            'phone'     => $phone
        ];

        $result = $UserModel->updateItem(session()->get('user_id'), $data);


        if($UserModel->errors()){
            return $this->failValidationErrors(json_encode($UserModel->errors()));
        }

        return $this->respond($result);
    }
    public function saveItemSettings()
    {
        $SettingsModel = model('SettingsModel');

        $code = $this->request->getVar('code');
        $value = $this->request->getVar('value');

        $data = [
            'code'      => $code,
            'value'     => $value
        ];

        $result = $SettingsModel->updateUserItem(session()->get('user_id'), $data);

        if($SettingsModel->errors()){
            return $this->failValidationErrors(json_encode($SettingsModel->errors()));
        }

        return $this->respond($result);
    }

    
    public function saveItemPassword()
    {
        $UserModel = model('UserModel');

        $old_password   = $this->request->getVar('old_password');
        $password       = $this->request->getVar('password');
        $password_confirm  = $this->request->getVar('password_confirm');


        $user_id = session()->get('user_id');

        $data = [
            'old_password'      => $old_password,
            'password'      => $password,
            'password_confirm' => $password_confirm
        ];

        $result = $UserModel->saveItemPassword($data, $user_id);

        if($result === 'wrong_password'){
            return $this->fail('wrong_password');
        }
        if($result === 'empty_password'){
            return $this->fail('empty_password');
        }
        if($result === 'different_password'){
            return $this->fail('different_password');
        }

        if($UserModel->errors()){
            return $this->failValidationErrors(json_encode($UserModel->errors()));
        }
        $auth_key = $UserModel->updateItemAuthKey($user_id);
        return $this->respond(['auth_key' => $auth_key]);
    }
    public function checkUsername()
    {
        $UserModel = model('UserModel');

        $username = $this->request->getVar('username');
        
        if($UserModel->checkUsername($username) &&  $username !== session()->get('user_data')['username']){
            return $this->respond($UserModel->getUsernameSuggestions($username)); 
        } 
        return $this->respond(false);
    }
    public function checkEmail()
    {
        $UserModel = model('UserModel');

        $email = $this->request->getVar('email');

        if(!$UserModel->checkEmail($email)  &&  $email !== session()->get('user_data')->email){
            return $this->respond(true); 
        } 
        return $this->fail('email_in_use'); 
    }
}
