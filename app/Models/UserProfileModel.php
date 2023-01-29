<?php

namespace App\Models;

use CodeIgniter\Model;

class UserProfileModel extends Model
{
    protected $table      = 'user_profiles';
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

        $user_statistics = $this->join('exercises', 'exercises.user_id = user_profiles.user_id')
        ->select("COALESCE(sum(exercises.points), 0) as total_points, COALESCE(COUNT(exercises.points), 0) as total_exercises")
        ->get()
        ->getRow();
        $profile->total_points      = $user_statistics->total_points;
        $profile->total_exercises   = $user_statistics->total_exercises;
        $profile->level             = $this->getItemLevel($user_statistics->total_points);
        $profile->total_classrooms  = count($UserClassroomModel->getList($user_id));
        return $profile;
    }
        
    public function itemCreate ($user_id)
    {
        $this->transBegin();
        $data = [
            'user_id'       => $user_id,
            'character_id'  => getenv('user_profile.character_id'),
            'rocket_id'     => getenv('user_profile.rocket_id'),
            'fellow_id'     => getenv('user_profile.fellow_id')
            
        ];
        $user_profile_id = $this->insert($data, true);
        $this->transCommit();

        return $user_profile_id;        
    }
    public function getItemLevel ($total_points) 
    {
        $level_data = $this->from('user_levels')->where(['points_from <=' => $total_points, 'points_to >' => $total_points])->get()->getRow();
        
        if(!empty($level_data)){
            $level_data->percentage = ceil($total_points * 100 / $level_data->points_to);
        }
        return $level_data;
    }



}