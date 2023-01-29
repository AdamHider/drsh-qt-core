<?php

namespace App\Models;

use CodeIgniter\Model;

class StatisticModel extends Model
{
    protected $table      = 'exercises';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'user_id', 
        'character_id', 
        'rocket_id', 
        'fellow_id'
    ];
    
    protected $useTimestamps = false;

    public function getItem ($user_id) 
    {
        $profile = $this->where('user_id', $user_id)->get()->getRow();

        $UserClassroomModel = model('UserClassroomModel');
        $profile->total_classrooms = count($UserClassroomModel->getList($user_id));
        
/*
        $profile->level = $this->getLevels($result['total_points']);
        
        $StatisticModel = model('Statistic');

        $user_statistics = $StatisticModel->getByFilter(false, "content");
        $profile->total_points = $user_statistics['total_points'];
        $profile->total_exercises = $user_statistics['total_exercises'];
        */
        return $profile;
    }



}