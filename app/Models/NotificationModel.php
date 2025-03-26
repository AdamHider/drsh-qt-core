<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    public function notifyLevel($level)
    {
        $UserUpdatesModel = model('UserUpdatesModel');
        $notification = [
            'user_id' => session()->get('user_id'),
            'code' => 'level',
            'data' => json_encode([
                'title' => 'Новый уровень!', 
                'description' => 'Вы достигли уровня '.$level['level'].'!',
                'image' => base_url('image/quests_rocket.png'),
                'data' => ['reward' => $level['reward']],
                'link' => '/user'
            ])
        ];
        $UserUpdatesModel->set($notification)->insert();
    }
    public function notifyAchievement($achievement)
    {
        $UserUpdatesModel = model('UserUpdatesModel');
        $notification = [
            'user_id' => session()->get('user_id'),
            'code' => 'achievement',
            'data' => json_encode([
                'title' => 'Новое достижение!', 
                'description' => 'Вы получили достижение "'.$achievement['title'].'"!',
                'image' => base_url('image/' . $achievement['image']),
                'data' => [],
                'link' => '/achievements'
            ])
        ];
        $UserUpdatesModel->set($notification)->insert();
    }
    public function notifyQuest($quest)
    {
        $UserUpdatesModel = model('UserUpdatesModel');
        $notification = [
            'user_id' => session()->get('user_id'),
            'code' => 'quest',
            'data' => json_encode([
                'title' => 'Задание выполнено!', 
                'description' => 'Вы выполнили задание "'.$quest['group']['title'].'"!',
                'image' => base_url('image/' . $quest['group']['image_full']),
                'data' => [],
                'link' => null
            ])
        ];
        $UserUpdatesModel->set($notification)->insert();
    }
    


}