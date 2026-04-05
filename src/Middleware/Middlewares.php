<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\App\Middleware;

use Osumi\OsumiFramework\Core\OMiddleware;

// Example only. Add your own.
OMiddleware::setGlobal([
  'before' => [
    // \Osumi\OsumiFramework\App\Middleware\CorsMiddleware::class,
  ],
  'afterRender' => [
    // \Osumi\OsumiFramework\App\Middleware\JsonEnvelopeMiddleware::class,
  ],
  'afterResponse' => [
    // \Osumi\OsumiFramework\App\Middleware\ResponseLogMiddleware::class,
  ]
]);
