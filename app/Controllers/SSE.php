<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class SSE extends Controller
{
    public function index($session_id)
    {
        $user_id = 0;
        if( $session_id && strlen($session_id) > 30 ){
            session_id($session_id);
            $user_id = session()->get('user_id');
            session_write_close();
        }
        header("Cache-Control: no-cache");
        header('Connection: keep-alive');
        header("Content-Type: text/event-stream");
        while ($user_id !== 0) {
            $message = $this->getMessageFromQueue($user_id);
            if(!empty($message)){
                echo "event:ping\n";
                echo "data:".json_encode($message)."\n\n";
                echo str_pad('',65536)."\n";
                ob_end_flush();
                flush();
            }
            if (connection_aborted()){
                exit();
            }
            sleep(1);
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