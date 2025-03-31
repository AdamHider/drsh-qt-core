<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use WebSocket\Client;
class Notification extends BaseController
{
    use ResponseTrait;

    public function getList()
    {
        $UserUpdatesModel = model('UserUpdatesModel');

        $notifications = $UserUpdatesModel->getList();
        
        if ($notifications == 'not_found') {
            return $this->failNotFound('not_found');
        }
    
        return $this->respond($notifications);
    }
}
