<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassroomUsermapModel extends Model
{
    protected $table      = 'classrooms_usermap';
    protected $primaryKey = 'user_id';

    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'user_id', 
        'classroom_id'
    ];
    
    protected $useTimestamps = false;

    public function getItem ($user_id, $classroom_id) 
    {
        $user_classroom = $this->where('user_id = '.$user_id.' AND item_id = '.$classroom_id)->get()->getRowArray(0);
        return $user_classroom;
    }
    public function getList ($user_id) 
    {
        $classrooms = $this->where('user_id', $user_id)->get()->getResult();
        return $classrooms;
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
        return;        
    }



}