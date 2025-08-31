<?php

namespace App\Controllers;
use App\Libraries\Notifier;

use CodeIgniter\API\ResponseTrait;
class User extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $UserModel = model('UserModel');

        $user = $UserModel->getItem();

        if ($user == 'not_found') {
            return $this->failNotFound('not_found');
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

        $name = $this->request->getVar('name');
        $username = $this->request->getVar('username');
        $email    = $this->request->getVar('email');
        $phone    = $this->request->getVar('phone');
        
        if(!$name){
            $name = session()->get('user_data')['name'];
        }
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
            'name'  => $name,
            'username'  => $username,
            'email'     => $email,
            'phone'     => $phone
        ];
        
        if(!$UserModel->checkEmail($email)){
            $data['email_verified'] = $UserModel->resetEmailVerification($email);
            $code = $UserModel->createEmailVerification($email);
            $notifier = new Notifier();
            $notifier->send(
                'user_email_verification',
                $email,
                [
                    'name' => $name,
                    'code'    => $code
                ]
            );
        } else {
            return $this->fail('email_in_use');
        }
        
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
    public function checkUsernameAuth()
    {
        $UserModel = model('UserModel');

        $username = $this->request->getVar('username');
        $result = $UserModel->checkUsername($username);
        if($result){
            return $this->respond($result);
        } 
        return $this->failNotFound('not_found');
    }
    public function checkEmail()
    {
        $UserModel = model('UserModel');

        $email = $this->request->getVar('email');

        if(!$UserModel->checkEmail($email)){
            return $this->respond(true); 
        } 
        return $this->fail('email_in_use'); 
    }
    public function checkEmailVerification()
    {
        $UserModel = model('UserModel');
        
        $code = $this->request->getVar('code');
        $result = $UserModel->checkEmailVerification($code);
        if($result){
            return $this->respond($result);
        } 
        return $this->fail('fail');
    }
    
    public function generateUsername()
    {
        $UserModel = model('UserModel');

        $name = $this->request->getVar('name');

        $result = $UserModel->generateUsername($name);
        if ($result == 'not_found') {
            return $this->failNotFound('not_found');
        }
        return $this->respond($result);
    }
    public function getItemInvitation()
    {
        $UserInvitationModel = model('UserInvitationModel');

        $result = $UserInvitationModel->getItem();
        if ($result == 'not_found') {
            return $this->failNotFound('not_found');
        }
        return $this->respond($result);
    }
}
