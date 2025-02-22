<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketServer extends Controller implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }
    public function onOpen(ConnectionInterface $conn)
    {
        // Сохраняем новое соединение
        $this->clients->attach($conn);
        echo "Новое соединение ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // Отправляем сообщение всем клиентам, кроме отправителя
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // Удаляем соединение
        $this->clients->detach($conn);
        echo "Соединение {$conn->resourceId} закрыто\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Ошибка: {$e->getMessage()}\n";
        $conn->close();
    }

    public function start()
    {
        $server = \Ratchet\Server\IoServer::factory(
            new \Ratchet\Http\HttpServer(
                new \Ratchet\WebSocket\WsServer(
                    new WebSocketServer()
                )
            ),
            8080
        );

        $server->run();
    }
}
