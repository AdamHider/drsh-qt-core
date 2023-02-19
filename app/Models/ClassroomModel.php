<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassroomModel extends Model
{
    use PermissionTrait;
    protected $table      = 'classrooms';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'image'
    ];
    public function getItem ($classroom_id) 
    {
        if(!$this->hasPermission($classroom_id, 'r')){
            return 'forbidden';
        }
        $DescriptionModel = model('DescriptionModel');
        $UserClassroomModel = model('UserClassroomModel');
        if ($classroom_id == 0) {
            return 'not_found';
        }
        $classroom = $this->where('id', $classroom_id)->get()->getRowArray();
        if ($classroom) {
            $classroom['description'] = $DescriptionModel->getItem('classroom', $classroom['id']);
            $classroom['image'] = base_url('image/' . $classroom['image']);
            $classroom['background_image'] = base_url('image/' . $classroom['background_image']);
            $classroom['is_subscribed'] = (bool) $UserClassroomModel->getItem((int) session()->get('user_id'), $classroom['id']);
        } else {
            return 'not_found';
        }
        return $classroom;
    }
    public function getList ($data) 
    {
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('users_to_classrooms', 'users_to_classrooms.classroom_id = classrooms.id')
            ->where('users_to_classrooms.user_id', $data['user_id']);
        }
        $classrooms = $this->get()->getResultArray();
        foreach($classrooms as &$classroom){
            $classroom['description'] = $DescriptionModel->getItem('classroom', $classroom['id']);
            $classroom['image'] = base_url('image/' . $classroom['image']);
            $classroom['background_image'] = base_url('image/' . $classroom['background_image']);
            $classroom['is_active'] = session()->get('user_data')['profile']['classroom_id'] == $classroom['id'];
        }
        return $classrooms;
    }
        
    public function itemCreate ($image)
    {
        $this->transBegin();
        $data = [
            'image' => $image
        ];
        $character_id = $this->insert($data, true);
        $this->transCommit();

        return $character_id;        
    }
    public function checkIfExists($code)
    {
        $classroom = $this->where('code', $code)->get()->getRow();
        return $classroom && $classroom->id;
    }
    


}