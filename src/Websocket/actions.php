<?php

declare(strict_types=1);

use Osumi\OsumiFramework\Plugins\OWebsocketAction;
use Osumi\OsumiFramework\App\Websocket\Modules\Authenticate\AuthenticateComponent;


OWebsocketAction::register('authenticate', AuthenticateComponent::class);
