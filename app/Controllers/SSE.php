<?php

namespace App\Controllers;

use CodeIgniter\Controller;

use CodeIgniter\API\ResponseTrait;

class SSE extends Controller
{
    use ResponseTrait;
    private $clients = [];
    private $queue = [];

    public function index()
    {
        // Отключение буферизации вывода
        ini_set('output_buffering', 'off');
        ini_set('zlib.output_compression', 'off');
        ini_set('implicit_flush', 'on');
        for ($i = 0; $i < ob_get_level(); $i++) {
            ob_end_flush();
        }
        ob_implicit_flush(1);

        // Установка заголовков для SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        // Идентификатор клиента
        $clientId = uniqid();
        $this->clients[$clientId] = $this->response->getBody();

        while (true) {
            // Отправка данных клиентам
            $messages = $this->getMessageFromQueue(112);
            if(!empty($messages)){
            $this->sendMessage($messages);
            }
            sleep(1); // Интервал между проверками
        }
    }

    public function sendMessage($message)
    {
        echo json_encode($message);
        ob_flush();
        flush();
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