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

        $id = $this->request->getVar('user_id');
        $username = $this->request->getVar('username');
        $email    = $this->request->getVar('email');
        $phone    = $this->request->getVar('phone');

        if ($id !== session()->get('user_id')) {
            return $this->fail('forbidden', 403);
        }
        $data = [
            'id'        => $id,
            'username'  => $username,
            'email'     => $email,
            'phone'     => $phone
        ];

        $result = $UserModel->updateItem($data);


        if($UserModel->errors()){
            return $this->failValidationErrors(json_encode($UserModel->errors()));
        }

        return $this->respond($result);
    }
    public function signUp()
    {
        $UserModel = model('UserModel');

        $username           = $this->request->getVar('username');
        $password           = $this->request->getVar('password');
        $password_confirm   = $this->request->getVar('passwordConfirm');
        $email              = $this->request->getVar('email');
        $phone              = $this->request->getVar('phone');

        $data = [
            'username'          => $username,
            'password'          => $password,
            'password_confirm'  => $password_confirm,
            'email'             => $email,
            'phone'             => $phone,
            'blocked'           => 0
        ];

        $this->signOutUser();

        $user_id = $UserModel->createItem($data);

        if($UserModel->errors()){
            return $this->failValidationErrors(json_encode($UserModel->errors()));
        }
        $user = $UserModel->getItem($user_id);

        return $this->respondCreated($user);
    }

    public function signIn()
    {
        $UserModel = model('UserModel');

        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');
        
        $this->signOutUser();

        $result = $UserModel->signIn($username, $password);

        if($result === 'not_found'){
            return $this->failNotFound('not_found');
        }
        if($result === 'wrong_password'){
            return $this->failUnauthorized('wrong_password');
        }
        if($result === 'blocked'){
            return $this->failForbidden('blocked');
        }

        if($result === 'success'){
            $user_id = session()->get('user_id');
            $user = $UserModel->getItem($user_id);
            if( !$user ){
                return $this->fail('fetch_error');
            }
            session()->set('user_data',$user);
            return $this->respond($user_id);
        }
        return $this->fail($result);
    }
    public function signOut()
    {
        if (session_status() === PHP_SESSION_ACTIVE){
            session_destroy();
        }
        return $this->respond('success');
    }
    public function signOutUser()
    {
        session_unset();
    }
    
    public function savePassword()
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

        return $this->respond($result);
    }
    public function checkUsername()
    {
        $UserModel = model('UserModel');

        $username = $this->request->getVar('username');
        
        if($UserModel->checkUsername($username) &&  $username !== session()->get('user_data')->username){
            return $this->respond($UserModel->getUsernameSuggestions($username)); 
        } 
        return $this->respond(false);
    }
    public function checkEmail(){
        $UserModel = model('UserModel');

        $email = $this->request->getVar('email');

        if(!$UserModel->checkEmail($email)  &&  $email !== session()->get('user_data')->email){
            return $this->respond(true); 
        } 
        return $this->fail('email_in_use'); 
    }
}
