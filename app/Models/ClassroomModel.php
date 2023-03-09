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
            $subscriber = $ClassroomUsermapModel->getItem((int) session()->get('user_id'), $classroom['id']);
            if(!empty($subscriber)){
                $classroom['is_subscribed'] = true;
                $classroom['is_disabled_subscriber'] = (bool) $subscriber['is_disabled'];
            }
            $classroom['image'] = base_url('image/' . $classroom['image']);
            $classroom['background_image'] = base_url('image/' . $classroom['background_image']);
            $classroom['is_owner'] = $classroom['owner_id'] == session()->get('user_id');
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
        $ClassroomUsermapModel = model('ClassroomUsermapModel');
        
        if($data['user_id']){
            $this->join('classrooms_usermap', 'classrooms_usermap.item_id = classrooms.id')
            ->where('classrooms_usermap.user_id', $data['user_id']);
        } else  {
            $this->join('classrooms_usermap', 'classrooms_usermap.item_id = classrooms.id AND classrooms_usermap.user_id = '.session()->get('user_id'), 'left');
        }
        if(isset($data['limit'])){
            $this->limit($data['limit'], $data['offset']);
        }
        $classrooms = $this->orderBy('(classrooms_usermap.item_id IS NOT NULL) DESC, (classrooms.owner_id ='.session()->get('user_id').') DESC')->get()->getResultArray();
        foreach($classrooms as &$classroom){
            $subscriber = $ClassroomUsermapModel->getItem((int) session()->get('user_id'), $classroom['id']);
            if(!empty($subscriber)){
                $classroom['is_subscribed'] = true;
                $classroom['is_disabled_subscriber'] = (bool) $subscriber['is_disabled'];
            }
            $classroom['image'] = base_url('image/' . $classroom['image']);
            $classroom['background_image'] = base_url('image/' . $classroom['background_image']);
            $classroom['is_owner'] = $classroom['owner_id'] == session()->get('user_id');
            $classroom['is_private'] = (bool) $classroom['is_private'];
            $classroom['subscribers_total'] = count($ClassroomUsermapModel->getUserList(['classroom_id' => $classroom['id'], 'is_disabled' => 0]));
            
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
            $subscriber['is_owner'] = (bool) $subscriber['is_owner'];
            $subscriber['is_classroom_owner'] = (bool) $subscriber['is_classroom_owner'];
            $subscriber['disabled_subscriber'] = (bool) $subscriber['disabled_subscriber'];
        }
        return $subscribers;
    }
    


}