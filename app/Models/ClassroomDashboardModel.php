<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassroomDashboardModel extends ClassroomModel
{
    use PermissionTrait;
    protected $table      = 'users';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = true;

    public function getItem($classroom_id)
    {
        $HomeworkModel = model('HomeworkModel');
        $QuestModel = model('QuestModel');
        $dashboard = [
            'total_subscribers' => $this->getSubscribersTotal($classroom_id),
            'total_quests' => $QuestModel->getTotal(['classroom_id' => $classroom_id]),
            'total_rank' => $this->getTotalRank(['classroom_id' => $classroom_id])
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
    public function getSubscribersTotal($classroom_id)
    {
        $ClassroomUsermapModel = model('ClassroomUsermapModel');
        $data = [
            'classroom_id' => $classroom_id
        ];
        $subscribers = $ClassroomUsermapModel->getUserList($data);
        return count($subscribers);
    }
    public function getTotalRank($classroom_id)
    {
        $ClassroomModel = model('ClassroomModel');
        $data = [
            'classroom_id' => $classroom_id
        ];
        $total = $ClassroomModel->join('classrooms_usermap', 'classrooms.id = classrooms_usermap.item_id')
        ->join('user_experience', 'user_experience.user_id = classrooms_usermap.user_id')
        ->select('SUM(user_experience.value) as total')->where('classrooms.id', $classroom_id)->get()->getRowArray();
        return $total;
    }

}