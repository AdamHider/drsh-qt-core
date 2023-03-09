<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassroomUsermapModel extends Model
{
    protected $table      = 'classrooms_usermap';
    protected $primaryKey = 'user_id';

    protected $allowedFields = [
        'user_id', 
        'item_id',
        'is_disabled'
    ];
    
    protected $useTimestamps = false;

    public function getItem ($user_id, $classroom_id) 
    {
        $user_classroom = $this->where('user_id = '.$user_id.' AND item_id = '.$classroom_id)->get()->getRowArray();
        return $user_classroom;
    }
    public function getList ($user_id) 
    {
        $classrooms = $this->where('user_id', $user_id)->get()->getResultArray();
        return $classrooms;
    }
    public function getUserList ($data) 
    {
        $this->join('users', 'users.id = classrooms_usermap.user_id')
        ->join('user_settings', 'user_settings.id = users.id', 'left')
        ->join('characters', 'characters.id = user_settings.character_id', 'left')
        ->join('classrooms', 'classrooms.id = classrooms_usermap.item_id')
        ->select('users.id as user_id, classrooms.code as classroom_code, 
                    users.username, user_settings.character_id, characters.avatar, characters.image, classrooms_usermap.is_disabled as disabled_subscriber, 
                    IF(classrooms_usermap.user_id = '.session()->get('user_id').', 1, 0) as is_owner, 
                    IF('.session()->get('user_id').' = classrooms.owner_id, 1, 0) as is_classroom_owner')
        ->where('item_id', $data['classroom_id']);
        if(isset($data['is_disabled'])){
            $this->where('classrooms_usermap.is_disabled', $data['is_disabled']);
        }
        if(isset($data['limit'])){
            $this->limit($data['limit'], $data['offset']);
        }
        $users = $this->get()->getResultArray();

        return $users;
    }
        
    public function createItem ($user_id, $classroom_id, $is_disabled)
    {
        $this->transBegin();
        $data = [
            'user_id'       => $user_id,
            'item_id'  => $classroom_id,
            'is_disabled'  => $is_disabled
        ];
        $this->insert($data, true);
        
        $this->transCommit();
        return true;        
    }
    public function updateItem ($data)
    {
        $ClassroomModel = model('ClassroomModel');
        if(!$ClassroomModel->hasPermission($data['item_id'], 'w')){
            return 'forbidden';
        }
        $this->transBegin();
        
        $this->update(['item_id'=>$data['item_id'], 'user_id'=>$data['user_id']], ['is_disabled' => $data['is_disabled']]);

        $this->transCommit();

        return true;        
    }
    public function itemDelete ($user_id, $classroom_id)
    {
        $this->transBegin();
        $this->where('user_id', $user_id)->where('item_id', $classroom_id)->delete();
        $this->transCommit();
        return true;        
    }

    


}