<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassroomUsermapModel extends Model
{
    protected $table      = 'classrooms_usermap';
    protected $primaryKey = 'user_id';

    protected $allowedFields = [
        'user_id', 
        'item_id'
    ];
    
    protected $useTimestamps = false;

    public function getItem ($user_id, $classroom_id) 
    {
        $user_classroom = $this->where('user_id = '.$user_id.' AND item_id = '.$classroom_id)->get()->getRowArray(0);
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
        ->select('users.id, users.username, user_settings.character_id, characters.avatar, characters.image')
        ->where('item_id', $data['classroom_id']);
        if(isset($data['limit'])){
            $this->limit($data['limit'], $data['offset']);
        }
        $users = $this->get()->getResultArray();

        return $users;
    }
        
    public function itemCreate ($user_id, $classroom_id)
    {
        $this->transBegin();
        $data = [
            'user_id'       => $user_id,
            'item_id'  => $classroom_id
        ];
        $this->insert($data, true);
        
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