<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class SSE extends Controller
{
    public function index($session_id)
    {
        //Auth
        if( $session_id && strlen($session_id) > 30 ){
            //session_id must be valid string not 'null'
            session_id($session_id);
        }
        session();
        // Устанавливаем заголовки для SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        // Чтение сообщений из очереди
        $data = $this->getMessageFromQueue(112);
        echo 'data' . "\n\n";
        if ($data) {
            foreach($data as $item){
                echo "data: " . json_encode($item['data']). "\n\n";
            }
            ob_flush();
            flush();
        }
    }

    private function getMessageFromQueue($user_id)
    {
        $UserUpdatesModel = model('UserUpdatesModel');
        $updates = $UserUpdatesModel->where('user_id', $user_id)->get()->getResultArray();
        if (!empty($updates)) {
            $UserUpdatesModel->where('user_id', $user_id)->delete();
            return $updates;
        }
        return null;
    }
}