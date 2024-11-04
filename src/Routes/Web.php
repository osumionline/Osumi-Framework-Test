<?php declare(strict_types=1);

use Osumi\OsumiFramework\Routing\ORoute;
use Osumi\OsumiFramework\App\Module\Home\Test\TestComponent;

ORoute::get('/test', TestComponent::class);
