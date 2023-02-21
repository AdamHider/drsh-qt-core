<?php

namespace App\Models;

use CodeIgniter\Model;

class AchievementModel extends Model
{
    protected $table      = 'achievements';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'image'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    private $achievementCodes = [
        'total_lessons',
        'total_points',
        'total_courses',
        'total_classrooms',
        'total_level',
        'total_achievements'
    ];

    public function getList ($data) 
    {
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('achievements_usermap', 'achievements_usermap.item_id = achievements.id')
            ->where('achievements_usermap.user_id', $data['user_id']);
        }
        $achievements = $this->limit($data['limit'], $data['offset'])->orderBy('code, value')->get()->getResultArray();
        
        if(empty($achievements)){
            return false;
        }
        foreach($achievements as &$achievement){
            $achievement['description'] = $DescriptionModel->getItem('achievement', $achievement['id']);
            $achievement['image'] = base_url('image/' . $achievement['image']);
            $achievement['progress'] = $this->calculateProgress($achievement);
        }
        return $achievements;
    }
    public function calculateProgress($data)
    {
        if ($data['code'] == 'total_lessons') {
            $current_progress = session()->get('user_data')['profile']['total_exercises'];
        } else
        if ($data['code'] == 'total_points') {
            $current_progress = session()->get('user_data')['profile']['total_points'];
        } else 
        if ($data['code'] == 'total_classrooms') {
            $current_progress = session()->get('user_data')['profile']['total_classrooms'];
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

    private function total_points_Compose($value = false){
        $StatisticModel = $this->getModel('Statistic');
        $student_statistic = $StatisticModel->getByFilter(false, "content");
        return $student_statistic['total_points'];
    }
    private function total_lessons_Compose($value = false){
        $StatisticModel = $this->getModel('Statistic');
        $student_statistic = $StatisticModel->getByFilter(false, "content");
        return $student_statistic['total_exercises'];
    }
    private function total_courses_Compose($value = false){
        
    }
    private function total_classrooms_Compose(){       
        $UserModel = $this->getModel('User');
        $student_total_classrooms = $UserModel->getTotalClassrooms();
        return $student_total_classrooms;
    }
    private function total_level_Compose(){
        $StatisticModel = $this->getModel('Statistic');
        $student_statistic = $StatisticModel->getByFilter(false, "content");
        $UserModel = $this->getModel('User');
        $level_data = $UserModel->getLevel($student_statistic['total_points']);
        return $level_data['id'];
    }
    private function total_achievements_Compose(){
        $AchievementModel = $this->getModel('Achievement');
        $total_achievements = $AchievementModel->getList();
        return count($total_achievements);
    }



}