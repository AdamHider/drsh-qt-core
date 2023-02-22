<?php

namespace App\Models;

use CodeIgniter\Model;

class UserDashboardModel extends UserModel
{
    use PermissionTrait;
    protected $table      = 'users';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = true;

    public function getItem($user_id)
    {
        $ClassroomUsermapModel = model('ClassroomUsermapModel');
        $ExerciseModel = model('ExerciseModel');

        $user_statistics = $ExerciseModel->where('user_id', $user_id)
        ->select("COALESCE(sum(points), 0) as total_points, COALESCE(COUNT(points), 0) as total_exercises")
        ->get()->getRowArray();

        $dashboard = [
            'total_points' => $user_statistics['total_points'],
            'total_exercises' => $user_statistics['total_exercises'],
            'level' => $this->getItemLevel($user_statistics['total_points']),
            'total_classrooms' => count($ClassroomUsermapModel->getList($user_id))
        ];

        return $dashboard;
    }
  
    public function getItemLevel ($total_points) 
    {
        $level_data = $this->from('user_levels')->where(['points_from <=' => $total_points, 'points_to >' => $total_points])->get()->getRowArray();
        
        if(!empty($level_data)){
            $level_data['percentage'] = ceil($total_points * 100 / $level_data['points_to']);
        }
        return $level_data;
    }

}