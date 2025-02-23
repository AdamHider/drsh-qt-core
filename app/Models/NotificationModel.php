<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    public function notify($data)
    {
        $UserUpdatesModel = model('UserUpdatesModel');
        $data = [
            'user_id' => session()->get('user_id'),
            'code' => $data['code'],
            'data' => json_encode($data['data'])
        ];
        $UserUpdatesModel->set($data)->insert();
    }
}