<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Controllers/WebSocketServer.php';

use App\Controllers\WebSocketServer;

$server = new WebSocketServer();
$server->start();