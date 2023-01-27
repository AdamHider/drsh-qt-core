<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class User extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        echo "Lesson Introduction";
        //return view('welcome_message');
    }
    public function signUp()
    {
        $UserModel = model('UserModel');

        $username           = $this->request->getVar('username');
        $password           = $this->request->getVar('password');
        $password_confirm   = $this->request->getVar('password_confirm');
        $email              = $this->request->getVar('email');
        $phone              = $this->request->getVar('phone');

        $username = '1234';
        $password = '1234';
        $password_confirm = '1234';

        $data = [
            'username'          => $username,
            'password'          => $password,
            'password_confirm'  => $password_confirm,
            'email'             => $email,
            'phone'             => $phone,
            'blocked'           => 0
        ];

        $user_id = $UserModel->itemCreate($data);

        if($UserModel->errors()){
            return $this->failValidationErrors(json_encode($UserModel->errors()));
        }

        return $this->respondCreated($user_id);
    }

    public function signIn()
    {
        $UserModel = model('UserModel');

        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');
        
        $username = '1234';
        $password = '1234';

        $result = $UserModel->signIn($username, $password);

        if($result == 'not_found'){
            return $this->failNotFound('not_found');
        }
        if($result == 'wrong_password'){
            return $this->failUnauthorized('wrong_password');
        }
        if($result == 'blocked'){
            return $this->failForbidden('blocked');
        }

        if($result == 'success'){
            $user = $UserModel->getActiveItem();
            if( !$user ){
                return $this->fail('fetch_error');
            }
            session()->set('user_id', $user->id);
            session()->set('user_data',$user);
            return $this->respond($user->id);
        }
        return $this->fail($result);
    }
    public function getItem()
    {
        $UserModel = model('UserModel');
        $result = $UserModel->getItem(1);
        return $this->respond($result, 200);
    }
    public function getList()
    {
        $UserModel = model('UserModel');
        $result = $UserModel->getList();
        return $this->respond($result, 200);
    }
}
