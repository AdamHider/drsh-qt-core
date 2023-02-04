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
        'classroom_id', 
        'course_id'
    ];
    
    protected $useTimestamps = false;

    public function getItem ($user_id) 
    {
        $profile = $this->where('user_id', $user_id)->get()->getRow();

        $UserClassroomModel = model('UserClassroomModel');

        $user_statistics = $this->join('exercises', 'exercises.user_id = user_profiles.user_id', 'left')
        ->select("COALESCE(sum(exercises.points), 0) as total_points, COALESCE(COUNT(exercises.points), 0) as total_exercises")
        ->get()->getRow();
        $profile->total_points = $user_statistics->total_points;
        $profile->total_exercises = $user_statistics->total_exercises;
        $profile->level = $this->getItemLevel($user_statistics->total_points);
        $profile->total_classrooms  = count($UserClassroomModel->getList($user_id));

        $CharacterModel = model('CharacterModel');
        $profile->character  = $CharacterModel->getItem($profile->character_id);
        return $profile;
    }
        
    public function createItem ($user_id)
    {
        $this->transBegin();
        
        $data = [
            'user_id'       => $user_id,
            'character_id'  => getenv('user_profile.character_id'),
            'classroom_id'  => getenv('user_profile.classroom_id'),
            'course_id'     => getenv('user_profile.course_id')
            
        ];
        $user_profile_id = $this->insert($data, true);

        $this->transCommit();

        return $user_profile_id;        
    }
    public function updateItem ($data)
    {
        $this->transBegin();

        $this->set($data);
        $this->where('user_id', $data['user_id']);
        $result = $this->update();

        $this->transCommit();

        return $result;        
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