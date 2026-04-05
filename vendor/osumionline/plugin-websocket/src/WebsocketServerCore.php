<?php

namespace Osumi\OsumiFramework\Plugins;

use Ratchet\Server\IoServer;

class WebsocketServerCore {
  private $server = null;

  public function run(): void {
    $this->server = IoServer::factory(
      new WebsocketServer(),
      8080
    );

    $this->server->run();
  }
}
