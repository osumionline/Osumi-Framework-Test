<?php

declare(strict_types=1);

namespace Osumi\OsumiFramework\Core;

use ReflectionClass;

final class OMiddleware {
  private const PHASE_BEFORE        = 'before';
  private const PHASE_AFTER_RENDER  = 'afterRender';
  private const PHASE_AFTER_RESPONSE= 'afterResponse';

  /**
   * List of global middlewares
   *
   * @var array{before: list<class-string>, afterRender: list<class-string>, afterResponse: list<class-string>}
   */
  private static array $global_middlewares = [
    'before'        => [],
    'afterRender'   => [],
    'afterResponse' => []
  ];

  /**
   * List of route middlewares
   *
   * @var array{before: list<class-string>, afterRender: list<class-string>, afterResponse: list<class-string>}
   */
  private static array $route_middlewares = [
    'before'        => [],
    'afterRender'   => [],
    'afterResponse' => []
  ];

  /**
   * Data loaded from executed middlewares
   *
   * @var array<string, array<string, mixed>>
   */
  private static array $context = [];

  private static bool          $is_error          = false;
  private static string | null $error_phase       = null;
  private static int           $error_status_code = 200;
  private static string        $error_message     = '';

  private static string $component_body = '';
  private static string $final_body     = '';

  /**
   * Headers to be sent with the response
   *
   * @var array<string, string>
   */
  private static array $headers = [];

  private static int $status_code = 200;

  public static function reset(): void {
    self::$route_middlewares = [
      'before'        => [],
      'afterRender'   => [],
      'afterResponse' => []
    ];

    self::$context = [];

    self::$is_error          = false;
    self::$error_phase       = null;
    self::$error_status_code = 200;
    self::$error_message     = '';

    self::$component_body = '';
    self::$final_body     = '';

    self::$headers     = [];
    self::$status_code = 200;
  }

  /**
   * Update middleware stored value
   *
   * @param array{before?: array, afterRender?: array, afterResponse?: array} $middlewares
   */
  public static function setGlobal(array $middlewares): void {
    self::$global_middlewares = self::normalizeMiddlewares($middlewares);
  }

  /**
   * Update route middleware stored value
   *
   * @param array{before?: array, afterRender?: array, afterResponse?: array} $middlewares
   */
  public static function setRoute(array $middlewares): void {
    self::$route_middlewares = self::normalizeMiddlewares($middlewares);
  }

  /**
   * Returns all the loaded middlewares
   *
   * @return array{before: list<class-string>, afterRender: list<class-string>, afterResponse: list<class-string>}
   */
  public static function getAll(): array {
    return [
      'before'        => array_merge(self::$global_middlewares['before'],        self::$route_middlewares['before']),
      'afterRender'   => array_merge(self::$global_middlewares['afterRender'],   self::$route_middlewares['afterRender']),
      'afterResponse' => array_merge(self::$global_middlewares['afterResponse'], self::$route_middlewares['afterResponse']),
    ];
  }

  /**
   * Return a normalized list of middlewares
   *
   * @param array{before?: array, afterRender?: array, afterResponse?: array} $middlewares
   */
  private static function normalizeMiddlewares(array $middlewares): array {
    $before        = array_key_exists('before', $middlewares)        ? (array)$middlewares['before']        : [];
    $after_render  = array_key_exists('afterRender', $middlewares)   ? (array)$middlewares['afterRender']   : [];
    $after_response= array_key_exists('afterResponse', $middlewares) ? (array)$middlewares['afterResponse'] : [];

    return [
      'before'        => array_values($before),
      'afterRender'   => array_values($after_render),
      'afterResponse' => array_values($after_response)
    ];
  }

  public static function setComponentBody(string $body): void {
    self::$component_body = $body;
  }

  public static function getComponentBody(): string {
    return self::$component_body;
  }

  public static function setFinalBody(string $body): void {
    self::$final_body = $body;
  }

  public static function getFinalBody(): string {
    return self::$final_body;
  }

  public static function setHeader(string $name, string $value): void {
    self::$headers[$name] = $value;
  }

  /** @return array<string, string> */
  public static function getHeaders(): array {
    return self::$headers;
  }

  public static function setStatusCode(int $status_code): void {
    self::$status_code = $status_code;
  }

  public static function getStatusCode(): int {
    return self::$status_code;
  }

  public static function isError(): bool {
    return self::$is_error;
  }

  public static function getErrorPhase(): string | null {
    return self::$error_phase;
  }

  public static function getErrorStatusCode(): int {
    return self::$error_status_code;
  }

  public static function getErrorMessage(): string {
    return self::$error_message;
  }

  /** @param array<string, mixed> $data */
  public static function runPhase(string $phase, array $data): array {
    $all  = self::getAll();
    $list = $all[$phase] ?? [];

    foreach ($list as $middleware_class) {
      // Uniform contract: static handle($phase, $data)
      /** @var array<string, mixed> $result */
      $result = $middleware_class::handle($phase, $data);

      // STOP
      if (array_key_exists('stop', $result) && $result['stop'] === true) {
        $status_code = (int)($result['status_code'] ?? 500);
        $message     = (string)($result['message'] ?? 'Middleware stopped execution');

        self::$is_error          = true;
        self::$error_phase       = $phase;
        self::$error_status_code = $status_code;
        self::$error_message     = $message;

        return [
          'stop'        => true,
          'status_code' => $status_code,
          'message'     => $message
        ];
      }

      // CONTEXT (merged under middleware short name)
      if (array_key_exists('context', $result) && is_array($result['context'])) {
        $mw_name = self::getMiddlewareName($middleware_class);
        if (!array_key_exists($mw_name, self::$context)) {
          self::$context[$mw_name] = [];
        }
        foreach ($result['context'] as $k => $v) {
          self::$context[$mw_name][(string)$k] = $v;
        }
      }

      // BODY updates
      if ($phase === self::PHASE_AFTER_RENDER && array_key_exists('body', $result)) {
        self::$component_body = (string)$result['body'];
      }
      if ($phase === self::PHASE_AFTER_RESPONSE && array_key_exists('body', $result)) {
        self::$final_body = (string)$result['body'];
      }

      // HEADERS/STATUS updates (mostly for afterResponse)
      if (array_key_exists('headers', $result) && is_array($result['headers'])) {
        foreach ($result['headers'] as $h => $v) {
          self::$headers[(string)$h] = (string)$v;
        }
      }
      if (array_key_exists('status_code', $result)) {
        self::$status_code = (int)$result['status_code'];
      }

      // Keep $data updated for downstream middlewares
      $data['component_body'] = self::$component_body;
      $data['final_body']     = self::$final_body;
      $data['headers']        = self::$headers;
      $data['status_code']    = self::$status_code;
      $data['is_error']       = self::$is_error;
      $data['error_phase']    = self::$error_phase;
      $data['error_message']  = self::$error_message;
      $data['error_status']   = self::$error_status_code;
    }

    return ['stop' => false];
  }

  public static function getContext(string $middleware, string $key): mixed {
    if (!array_key_exists($middleware, self::$context)) {
      return null;
    }
    return array_key_exists($key, self::$context[$middleware]) ? self::$context[$middleware][$key] : null;
  }

  /** @return array<string, mixed> */
  public static function getMiddlewareContext(string $middleware): array {
    return array_key_exists($middleware, self::$context) ? self::$context[$middleware] : [];
  }

  private static function getMiddlewareName(string $middleware_class): string {
    $reflection = new ReflectionClass($middleware_class);
    $name = $reflection->getShortName();
    return str_ireplace('Middleware', '', $name);
  }
}
