<?php

namespace App\Models;

use CodeIgniter\Model;

class UserClassroomModel extends Model
{
    protected $table      = 'user_classrooms';
    protected $primaryKey = 'user_id';

    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'user_id', 
        'classroom_id'
    ];
    
    protected $useTimestamps = false;

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
            'classroom_id'  => $classroom_id
            
        ];
        $this->insert($data, true);
        
        $this->transCommit();
        return;        
    }



}