<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\Routes;

use Osumi\OsumiFramework\Routing\ORoute;
use Osumi\OsumiFramework\App\Layout\DefaultLayoutComponent;
use Osumi\OsumiFramework\App\Module\Home\Middleware\MiddlewareComponent;
use Osumi\OsumiFramework\App\Module\Home\Start\StartComponent;
use Osumi\OsumiFramework\App\Module\Home\User\UserComponent;
use Osumi\OsumiFramework\App\Middleware\LoginMiddleware;
use Osumi\OsumiFramework\App\Middleware\UserMiddleware;

ORoute::layout(DefaultLayoutComponent::class, function() {
  ORoute::get('/middleware',    MiddlewareComponent::class, ['before' => [LoginMiddleware::class, UserMiddleware::class]]);
  ORoute::get('/',              StartComponent::class);
  ORoute::get('/user/:id_user', UserComponent::class,       ['before' => [UserMiddleware::class]]);
});
