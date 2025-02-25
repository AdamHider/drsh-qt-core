<?php

namespace App\Models;

use CodeIgniter\Model;

class AchievementModel extends Model
{
    protected $table      = 'achievements';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';

    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getList ($data) 
    {
        $DescriptionModel = model('DescriptionModel');
        
        $this->join('achievement_groups', 'achievement_groups.id = achievements.group_id');
        if($data['user_id']){
            $this->join('achievements_usermap', 'achievements_usermap.item_id = achievements.id')
            ->where('achievements_usermap.user_id', $data['user_id']);
        }
        $achievements = $this->select('achievements.*, achievement_groups.code')->limit($data['limit'], $data['offset'])->orderBy('code, value')->get()->getResultArray();
        
        if(empty($achievements)){
            return false;
        }
        foreach($achievements as &$achievement){
            $achievement = array_merge($achievement, $DescriptionModel->getItem('achievement', $achievement['id']));
            $achievement['group'] = $DescriptionModel->getItem('achievement_group', $achievement['group_id']);
            $achievement['image'] = base_url('image/' . $achievement['image']);
            $achievement['progress'] = $this->calculateProgress($achievement);
        }
        return $achievements;
    }
    public function calculateProgress ($data)
    {
        $ExerciseModel = model('ExerciseModel');
        $statistics = $ExerciseModel->where('user_id', session()->get('user_id'))
        ->select("COALESCE(sum(points), 0) as total_points, COALESCE(COUNT(points), 0) as total_exercises")
        ->get()->getRowArray();
        
        if ($data['code'] == 'total_lessons') {
            $current_progress = $statistics['total_exercises'];
        } else
        if ($data['code'] == 'total_points') {
            $current_progress = $statistics['total_points'];
        } else {
            $current_progress = 0;
        }
        return [
            'current' => $current_progress,
            'target' => $data['value'],
            'percentage' => ceil($current_progress * 100 / $data['value']),
            'is_done' => $current_progress >=  $data['value']
        ];
    }
    private function total_points_Compose ($value = false){
        $StatisticModel = $this->getModel('Statistic');
        $student_statistic = $StatisticModel->getByFilter(false, "content");
        return $student_statistic['total_points'];
    }
    private function total_lessons_Compose ($value = false){
        $StatisticModel = $this->getModel('Statistic');
        $student_statistic = $StatisticModel->getByFilter(false, "content");
        return $student_statistic['total_exercises'];
    }
    private function total_courses_Compose ($value = false){
        
    }
    private function total_classrooms_Compose ()
    {       
        $UserModel = $this->getModel('User');
        $student_total_classrooms = $UserModel->getTotalClassrooms();
        return $student_total_classrooms;
    }
    private function total_level_Compose ()
    {
        $StatisticModel = $this->getModel('Statistic');
        $student_statistic = $StatisticModel->getByFilter(false, "content");
        $UserModel = $this->getModel('User');
        $level_data = $UserModel->getLevel($student_statistic['total_points']);
        return $level_data['id'];
    }
    private function total_achievements_Compose ()
    {
        $AchievementModel = $this->getModel('Achievement');
        $total_achievements = $AchievementModel->getList();
        return count($total_achievements);
    }



}