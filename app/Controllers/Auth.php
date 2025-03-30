<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Auth extends BaseController
{
    use ResponseTrait;

    public function signUp()
    {
        $UserModel = model('UserModel');

        $name               = $this->request->getVar('name') ?? null;
        $username           = $this->request->getVar('username') ?? null;
        $password           = $this->request->getVar('password');
        $password_confirm   = $this->request->getVar('passwordConfirm');
        $gender             = $this->request->getVar('gender') ?? null;
        $inviter_hash       = $this->request->getVar('inviter_hash') ?? null;

        $data = [
            'name'              => $name,
            'username'          => $username,
            'password'          => $password,
            'password_confirm'  => $password_confirm,
            'gender'            => $gender,
            'inviter_hash'      => $inviter_hash,
            'blocked'           => 0
        ];

        $this->signOutUser();
        
        $auth_key = $UserModel->createItem($data);

        if($UserModel->errors()){
            return $this->failValidationErrors(json_encode($UserModel->errors()));
        }

        return $this->respondCreated(['auth_key' => $auth_key]);
    }

    public function getAuth()
    {
        $UserModel = model('UserModel');

        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');

        $result = $UserModel->getItemAuth($username, $password);
        $next_attempt = $this->checkAttemptsExpired();
        if($next_attempt){
            return $this->fail('too_many_attempts');
        }
        if($result === 'not_found'){
            return $this->failNotFound('not_found');
        }
        if($result === 'wrong_password'){
            return $this->failUnauthorized('wrong_password');
        }
        if($result === 'blocked'){
            return $this->failForbidden('blocked');
        }
        if($UserModel->errors()){
            $this->fail($result);
        }

        return $this->respond(['auth_key' => $result]);
    }
    public function signIn()
    {
        $UserModel = model('UserModel');

        $auth_key = $this->request->getVar('auth_key');
        
        $this->signOutUser();

        $result = $UserModel->signIn($auth_key);

        if($result === 'not_found'){
            return $this->failNotFound('not_found');
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
            session()->set('login_attempts', []);
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

    private function checkAttemptsExpired () 
    {
        $login_attempts = session()->get('login_attempts') ?? [];
        $now = date('Y-m-d H:i:s');
        if(!empty($login_attempts)){
            $last_attempt = end($login_attempts);
            $differenceInSeconds = strtotime($now) - strtotime($last_attempt);
            if($differenceInSeconds > 600){
                $login_attempts = [];
            }
            if(count($login_attempts) >= 5) {
                $next_attempt = date('Y-m-d H:i:s', strtotime($last_attempt) + 120);
                if($differenceInSeconds < 120){
                    return $next_attempt;
                } else {
                    array_shift($login_attempts);
                }
            }
        }
        $login_attempts[] = $now;
        session()->set('login_attempts', $login_attempts);
        return false;
    }
}
