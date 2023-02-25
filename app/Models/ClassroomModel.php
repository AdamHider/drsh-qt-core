<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassroomModel extends Model
{
    use PermissionTrait;
    protected $table      = 'classrooms';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'owner_id',
        'code',
        'title',
        'description',
        'image',
        'background_image',
        'is_private',
        'is_disabled'
    ];
    protected $validationRules    = [
        'title'     => [
            'label' =>'title',
            'rules' =>'required|min_length[3]|is_unique[classrooms.title,id,{id}]',
            'errors'=>[
                'required'=>'required',
                'min_length'=>'short',
                'is_unique'=>'notunique'
            ]
        ],
        'description'     => [
            'label' =>'description',
            'rules' =>'permit_empty|min_length[4]',
            'errors'=>[
                'min_length'=>'short'
            ]
        ]
    ];
    public function getItem ($classroom_id) 
    {
        if(!$this->hasPermission($classroom_id, 'r')){
            return 'forbidden';
        }
        $ClassroomUsermapModel = model('ClassroomUsermapModel');
        if ($classroom_id == 0) {
            return 'not_found';
        }
        $classroom = $this->where('id', $classroom_id)->get()->getRowArray();
        if ($classroom) {
            $classroom['image'] = base_url('image/' . $classroom['image']);
            $classroom['background_image'] = base_url('image/' . $classroom['background_image']);
            $classroom['is_owner'] = $classroom['owner_id'] == session()->get('user_id');
            $classroom['is_subscribed'] = (bool) $ClassroomUsermapModel->getItem((int) session()->get('user_id'), $classroom['id']);
            $classroom['is_private'] = (bool) $classroom['is_private'];
            $ClassroomDashboardModel = model('ClassroomDashboardModel');
            $classroom['dashboard'] = $ClassroomDashboardModel->getItem($classroom['id']);
        } else {
            return 'not_found';
        }
        return $classroom;
    }
    public function getList ($data) 
    {
        if($data['user_id']){
            $this->join('classrooms_usermap', 'classrooms_usermap.item_id = classrooms.id')
            ->where('classrooms_usermap.user_id', $data['user_id']);
        }
        if(isset($data['limit'])){
            $this->limit($data['limit'], $data['offset']);
        }
        $classrooms = $this->get()->getResultArray();
        foreach($classrooms as &$classroom){
            $classroom['image'] = base_url('image/' . $classroom['image']);
            $classroom['background_image'] = base_url('image/' . $classroom['background_image']);
            $classroom['is_active'] = session()->get('user_data')['settings']['classroom_id'] == $classroom['id'];
        }
        return $classrooms;
    }
        
    public function createItem ()
    {
        $this->validationRules = [];
        $code = substr(md5(time()), 0, 6);
        $data = [
            'owner_id'          => session()->get('user_id'),
            'code'              => $code,
            'title'             => lang('App.classroom.default.title').' '.$code,
            'description'       => lang('App.classroom.default.description'),
            'image'             => getenv('image.image.placeholder'),
            'background_image'  => getenv('image.background_image.placeholder'),
            'is_private'        => false,
            'is_disabled'       => true
        ];
        $this->transBegin();
        $classroom_id = $this->insert($data, true);

        $this->transCommit();

        return $classroom_id;        
    }
    public function updateItem ($data)
    {
        if(!$this->hasPermission($data['id'], 'w')){
            return 'forbidden';
        }
        $this->transBegin();
        
        $this->update(['id'=>$data['id']], $data);

        $this->transCommit();

        return true;        
    }

    public function checkIfExists($code)
    {
        return $this->where('code', $code)->get()->getRow('id');
    }
    public function getSubscribers($data)
    {
        if(isset($data['classroom_id'])){
            if(!$this->hasPermission($data['classroom_id'], 'r')){
                return 'forbidden';
            }
        }
        $ClassroomUsermapModel = model('ClassroomUsermapModel');
        $subscribers = $ClassroomUsermapModel->getUserList($data);
        foreach($subscribers as &$subscriber){
            if(!$subscriber['avatar']){
                $subscriber['avatar'] = getenv('character.avatar.placeholder');
            }
            if(!$subscriber['image']){
                $subscriber['image'] = getenv('character.image.placeholder');
            }
            $subscriber['avatar'] = base_url('image/' . $subscriber['avatar']);
            $subscriber['image'] = base_url('image/' . $subscriber['image']);
        }
        return $subscribers;
    }
    


}