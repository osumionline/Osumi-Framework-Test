<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\Plugins;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebsocketServer implements MessageComponentInterface {
    public function onOpen(ConnectionInterface $conn) {
    }

    public function onMessage(ConnectionInterface $from, $msg) {
    }

    public function onClose(ConnectionInterface $conn) {
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
    }
}
