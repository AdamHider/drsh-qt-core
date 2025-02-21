<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    public function notify($data)
    {
        $UserUpdatesModel = model('UserUpdatesModel');
        $UserUpdatesModel->insert([
            'user_id' => session()->get('user_id'),
            'data' => json_encode($data)
        ]);
    }
}