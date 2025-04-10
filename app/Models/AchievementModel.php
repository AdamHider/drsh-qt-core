<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Events\Events;

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
        
        $this->join('achievement_groups', 'achievement_groups.id = achievements.group_id AND achievement_groups.is_published = 1');
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
            $achievement['image'] = base_url('image/index.php'.$achievement['image']);
            $achievement['progress'] = $this->calculateProgress($achievement);
        } 

        $LessonDailyModel = model('LessonDailyModel');
        $LessonDailyModel->createItem('daily_lexis');
        $LessonDailyModel->createItem('daily_chat');

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
        $percentage = ceil($current_progress * 100 / $data['value']);
        if($percentage > 100){
            $percentage = 100;
        }
        return [
            'current' => $current_progress,
            'target' => $data['value'],
            'percentage' => $percentage,
            'is_done' => $current_progress >=  $data['value']
        ];
    }

    public function getListToLink ($code) 
    {
        $ExerciseModel = model('ExerciseModel');
        $AchievementUsermapModel = model('AchievementUsermapModel');
        $UserLevelModel = model('UserLevelModel');
        $DescriptionModel = model('DescriptionModel');

        $this->select('achievements.*, achievement_groups.code')->join('achievement_groups', 'achievement_groups.id = achievements.group_id')
        ->join('achievements_usermap', 'achievements.id = achievements_usermap.item_id AND achievements_usermap.user_id = '.session()->get('user_id'), 'left')
        ->where('code', $code)->where('achievements_usermap.item_id IS NULL');

        if($code == 'total_lessons'){
            $statistics = $ExerciseModel->where('user_id', session()->get('user_id'))->select("COALESCE(COUNT(points), 0) as total_lessons")->get()->getRowArray();
            $this->where('value <= '.$statistics['total_lessons']);
        }
        if($code == 'total_points'){
            $statistics = $ExerciseModel->where('user_id', session()->get('user_id'))->select("COALESCE(SUM(points), 0) as total_points")->get()->getRowArray();
            $this->where('value <= '.$statistics['total_points']);
        }
        if($code == 'total_achievements'){
            $statistics = $AchievementUsermapModel->where('user_id', session()->get('user_id'))->select("COALESCE(COUNT(item_id), 0) as total_achievements")->get()->getRowArray();
            $this->where('value <= '.$statistics['total_achievements']);
        }

        if($code == 'total_level'){
            $user_level = $UserLevelModel->getCurrentItem();
            $this->where('value <= '.$user_level['level']);
        }
        $achievements = $this->get()->getResultArray();

        foreach($achievements as &$achievement){
            $achievement = array_merge($achievement, $DescriptionModel->getItem('achievement', $achievement['id']));
        }
        return $achievements;
    }

    public function linkItem($achievement)
    {
        $AchievementUsermapModel = model('AchievementUsermapModel');
        $AchievementUsermapModel->ignore()->insert(['item_id' => $achievement['id'], 'user_id' => session()->get('user_id')]);
        Events::trigger('achievementGained', $achievement['id']);
    }
}