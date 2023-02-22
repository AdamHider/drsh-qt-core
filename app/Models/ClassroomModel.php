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
        $ClassroomUsermapModel = model('ClassroomUsermapModel');
        if ($classroom_id == 0) {
            return 'not_found';
        }
        $classroom = $this->where('id', $classroom_id)->get()->getRowArray();
        if ($classroom) {
            $classroom['image'] = base_url('image/' . $classroom['image']);
            $classroom['background_image'] = base_url('image/' . $classroom['background_image']);
            $classroom['is_subscribed'] = (bool) $ClassroomUsermapModel->getItem((int) session()->get('user_id'), $classroom['id']);
            $classroom['is_private'] = (bool) $classroom['is_private'];
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
        $classrooms = $this->get()->getResultArray();
        foreach($classrooms as &$classroom){
            $classroom['image'] = base_url('image/' . $classroom['image']);
            $classroom['background_image'] = base_url('image/' . $classroom['background_image']);
            $classroom['is_active'] = session()->get('user_data')['settings']['classroom_id'] == $classroom['id'];
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