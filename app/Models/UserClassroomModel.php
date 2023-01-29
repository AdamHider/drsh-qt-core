<?php

namespace App\Models;

use CodeIgniter\Model;

class UserClassroomModel extends Model
{
    protected $table      = 'user_classrooms';
    protected $primaryKey = 'user_id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'user_id', 
        'classroom_id'
    ];
    
    protected $useTimestamps = false;

    public function getList ($user_id) 
    {
        $classrooms = $this->where('user_id', $user_id)->get()->getRowArray();
        if(empty($classrooms)){
            $classrooms = [];
        }
        return $classrooms;
    }
        
    public function itemCreate ($user_id, $classroom_id)
    {
        $this->transBegin();
        $data = [
            'user_id'       => $user_id,
            'classroom_id'  => $classroom_id
            
        ];
        $user_profile_id = $this->insert($data, true);
        $this->transCommit();

        return $user_profile_id;        
    }



}