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
            $updates = $this->getUpdates($user_id);
            $UserUpdatesModel = model('UserUpdatesModel');
            if(!empty($updates)){
                foreach($updates as $update){
                    $data = json_decode($update['data'], true);
                    $data['id'] = $update['id'];
                    $data['code'] = $update['code'];
                    echo "event:".$update['code']."\n";
                    echo "data:".json_encode($data)."\n\n";
                }
                $UserUpdatesModel->set('status', 'finished')->where('user_id', $user_id)->update();
                $UserUpdatesModel->where('user_id', $user_id)->where('created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)')->delete();
            }
            echo str_pad('',65536)."\n";
            if (ob_get_contents()) ob_get_flush();
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
        return $UserUpdatesModel->where('user_id', $user_id)->where('status', 'created')->get()->getResultArray();
    }
}