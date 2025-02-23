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
        ini_set('max_execution_time', 0);
        header("Cache-Control: no-cache");
        header('Connection: keep-alive');
        header("Content-Type: text/event-stream");
        $i = 0;
        while (1) {
            $i++;
            $UserUpdatesModel = model('UserUpdatesModel');
            $updates = $this->getUpdates($user_id);
            if(!empty($updates)){
                foreach($updates as $update){
                    echo "event:".$update['code']."\n";
                    echo "data:".json_encode($update['data'])."\n\n";
                }
                $UserUpdatesModel->where('user_id', $user_id)->delete();
            }
            echo str_pad('',65536)."\n";
            ob_get_flush();
            flush();
            if (connection_aborted()){
                exit();
            }
            sleep(1);
        }
    }
    private function getUpdates($user_id)
    {
        $UserUpdatesModel = model('UserUpdatesModel');
        return $UserUpdatesModel->where('user_id', $user_id)->get()->getResultArray();
    }
}