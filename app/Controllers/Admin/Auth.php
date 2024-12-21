<?php 

namespace App\Controllers\Admin;

use App\Models\UserModel;
use App\Controllers\BaseController;

class Auth extends BaseController
{
    public $data;
    public function login()
    {

        $this->data['settings'] = [
            'layout' => 'default',
            'menu' => [
                'id' => 2
            ],
            'title' => 'Dashboard',
            'path' => '/admin/dashboard'
        ];
        return view('auth/login', $this->data);
    }
    public function authenticate()
    {
        $session = session();
        $UserModel = new UserModel();
        
        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');

        $auth_key = $UserModel->getItemAuth($username, $password);

        if($auth_key === 'not_found'){
            $session->setFlashdata('msg', 'Username not found');
            return redirect()->to('/auth/login');
        }
        if($auth_key === 'blocked'){
            $session->setFlashdata('msg', 'Username blocked');
            return redirect()->to('/auth/login');
        }
        if($auth_key === 'wrong_password'){
            $session->setFlashdata('msg', 'Wrong password');
            return redirect()->to('/auth/login');
        }

        $UserModel->signIn($auth_key);
        
        $user_id = session()->get('user_id');
        $user = $UserModel->getItem($user_id);
        if( !$user ){
            $session->setFlashdata('msg', 'Auth expired');
            return redirect()->to('/auth/login');
        }
        session()->set('user_data',$user);
        return redirect()->to('/admin/dashboard');
    }

    public function logout()
    {
        $session = session();
        $session->destroy();
        return redirect()->to('/auth/login');
    }

    public function register()
    {
        $this->data['settings'] = [
            'layout' => 'default',
            'menu' => [
                'id' => 2
            ],
            'title' => 'Register',
            'path' => '/auth/register'
        ];

        return view('auth/register', $this->data);
    }

    public function store()
    {
        $session = session();
        $UserModel = new UserModel();

        $username           = $this->request->getVar('username') ?? null;
        $password           = $this->request->getVar('password');
        $password_confirm   = $this->request->getVar('passwordConfirm');

        $data = [
            'username'          => $username,
            'password'          => $password,
            'password_confirm'  => $password_confirm,
            'blocked'           => 0
        ];
        
        $UserModel->createItem($data);
    
        if($UserModel->errors()){
            $session->setFlashdata('msg', 'Wrong Password');
            $data['validation'] = $UserModel->errors();
            return redirect()->to('/auth/register');
        }
        $this->data['settings'] = [
            'layout' => 'default',
            'menu' => [
                'id' => 2
            ],
            'title' => 'Register',
            'path' => '/auth/register'
        ];

        return redirect()->to('/auth/login');
    }

    
}