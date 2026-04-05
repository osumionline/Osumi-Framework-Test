<?php

declare(strict_types=1);

namespace Osumi\OsumiFramework\Routing;

/**
 * ORoute
 *
 * Static router definition registry.
 *
 * This class is used by application route files to register routes at bootstrap time.
 * Routes are stored in a static array and later consumed by OUrl when matching a request.
 *
 * In 9.9, "filters" are removed and replaced by "middlewares" with three phases:
 * - before
 * - afterRender
 * - afterResponse
 */
class ORoute {
  /**
   * Registered routes list.
   *
   * Each route is stored as an associative array with a canonical shape:
   * - method: string (GET/POST/PUT/DELETE)
   * - url: string (full URL including group prefix)
   * - component: string (FQCN of a component, or file path for views)
   * - middlewares: array{before: array, afterRender: array, afterResponse: array}
   * - layout: string|null (FQCN of a layout component)
   * - is_view: bool (true when the route is a static view file)
   *
   * @var array<int, array{
   *   method: string,
   *   url: string,
   *   component: string,
   *   middlewares: array{before: array, afterRender: array, afterResponse: array},
   *   layout: string|null,
   *   is_view: bool
   * }>
   */
  public static array $routes = [];

  /** @var string Current prefix accumulated by prefix()/group(). */
  private static string $current_prefix = '';

  /** @var string|null Current layout accumulated by layout()/group(). */
  private static string | null $current_layout = null;

  /**
   * Current middlewares accumulated by prefix()/layout()/group().
   *
   * This is merged into any route declared inside those groups.
   *
   * @var array{before: array, afterRender: array, afterResponse: array}
   */
  private static array $current_middlewares = [
    'before' => [],
    'afterRender' => [],
    'afterResponse' => []
  ];

  /**
   * Register a new GET route.
   *
   * @param string $url URL pattern to respond (can include dynamic segments like "/user/:id").
   * @param string $component Component FQCN to execute.
   * @param array{before?: array, afterRender?: array, afterResponse?: array} $middlewares Middlewares applied to this route.
   * @param string|null $layout Optional layout FQCN (overridden by current group layout if set).
   *
   * @return void
   */
  public static function get(string $url, string $component, array $middlewares = [], string | null $layout = null): void {
    $full_url = self::$current_prefix . $url;
    $layout = (!is_null(self::$current_layout)) ? self::$current_layout : $layout;

    self::addRoute(
      'GET',
      $full_url,
      $component,
      self::mergeMiddlewares(self::$current_middlewares, $middlewares),
      $layout
    );
  }

  /**
   * Register a new POST route.
   *
   * @param string $url URL pattern to respond.
   * @param string $component Component FQCN to execute.
   * @param array{before?: array, afterRender?: array, afterResponse?: array} $middlewares Middlewares applied to this route.
   * @param string|null $layout Optional layout FQCN (overridden by current group layout if set).
   *
   * @return void
   */
  public static function post(string $url, string $component, array $middlewares = [], string | null $layout = null): void {
    $full_url = self::$current_prefix . $url;
    $layout = (!is_null(self::$current_layout)) ? self::$current_layout : $layout;

    self::addRoute(
      'POST',
      $full_url,
      $component,
      self::mergeMiddlewares(self::$current_middlewares, $middlewares),
      $layout
    );
  }

  /**
   * Register a new PUT route.
   *
   * @param string $url URL pattern to respond.
   * @param string $component Component FQCN to execute.
   * @param array{before?: array, afterRender?: array, afterResponse?: array} $middlewares Middlewares applied to this route.
   * @param string|null $layout Optional layout FQCN (overridden by current group layout if set).
   *
   * @return void
   */
  public static function put(string $url, string $component, array $middlewares = [], string | null $layout = null): void {
    $full_url = self::$current_prefix . $url;
    $layout = (!is_null(self::$current_layout)) ? self::$current_layout : $layout;

    self::addRoute(
      'PUT',
      $full_url,
      $component,
      self::mergeMiddlewares(self::$current_middlewares, $middlewares),
      $layout
    );
  }

  /**
   * Register a new DELETE route.
   *
   * @param string $url URL pattern to respond.
   * @param string $component Component FQCN to execute.
   * @param array{before?: array, afterRender?: array, afterResponse?: array} $middlewares Middlewares applied to this route.
   * @param string|null $layout Optional layout FQCN (overridden by current group layout if set).
   *
   * @return void
   */
  public static function delete(string $url, string $component, array $middlewares = [], string | null $layout = null): void {
    $full_url = self::$current_prefix . $url;
    $layout = (!is_null(self::$current_layout)) ? self::$current_layout : $layout;

    self::addRoute(
      'DELETE',
      $full_url,
      $component,
      self::mergeMiddlewares(self::$current_middlewares, $middlewares),
      $layout
    );
  }

  /**
   * Register a static view file route.
   *
   * This is used for serving prebuilt templates (like docs pages).
   *
   * @param string $url URL pattern to respond.
   * @param string $file View file path relative to app dir.
   * @param array{before?: array, afterRender?: array, afterResponse?: array} $middlewares Middlewares applied to this view.
   * @param string|null $layout Optional layout FQCN (overridden by current group layout if set).
   *
   * @return void
   */
  public static function view(string $url, string $file, array $middlewares = [], string | null $layout = null): void {
    $full_url = self::$current_prefix . $url;
    $layout = (!is_null(self::$current_layout)) ? self::$current_layout : $layout;

    self::addRoute(
      'GET',
      $full_url,
      $file,
      self::mergeMiddlewares(self::$current_middlewares, $middlewares),
      $layout,
      true
    );
  }

  /**
   * Add a route to the internal registry.
   *
   * @param string $method HTTP method (GET/POST/PUT/DELETE).
   * @param string $url Full URL pattern (including group prefix).
   * @param string $component Component FQCN or view file relative path.
   * @param array{before: array, afterRender: array, afterResponse: array} $middlewares Normalized middlewares.
   * @param string|null $layout Layout FQCN or null.
   * @param bool $is_view Whether this route serves a view file.
   *
   * @return void
   */
  public static function addRoute(
    string $method,
    string $url,
    string $component,
    array $middlewares,
    string | null $layout = null,
    bool $is_view = false
  ): void {
    self::$routes[] = [
      'method' => $method,
      'url' => $url,
      'component' => $component,
      'middlewares' => $middlewares,
      'layout' => $layout,
      'is_view' => $is_view
    ];
  }

  /**
   * Register a group of routes with a URL prefix and optional middlewares.
   *
   * @param string $prefix Prefix to be applied (e.g. "/api").
   * @param callable $callback Callback that will register routes inside this group.
   * @param array{before?: array, afterRender?: array, afterResponse?: array} $middlewares Group middlewares to accumulate.
   *
   * @return void
   */
  public static function prefix(string $prefix, callable $callback, array $middlewares = []): void {
    $previous_prefix = self::$current_prefix;
    $previous_middlewares = self::$current_middlewares;

    self::$current_prefix = $prefix;
    self::$current_middlewares = self::mergeMiddlewares(self::$current_middlewares, $middlewares);

    $callback();

    self::$current_prefix = $previous_prefix;
    self::$current_middlewares = $previous_middlewares;
  }

  /**
   * Register a group of routes with a layout and optional middlewares.
   *
   * @param string $layout Layout FQCN to apply.
   * @param callable $callback Callback that will register routes inside this group.
   * @param array{before?: array, afterRender?: array, afterResponse?: array} $middlewares Group middlewares to accumulate.
   *
   * @return void
   */
  public static function layout(string $layout, callable $callback, array $middlewares = []): void {
    $previous_layout = self::$current_layout;
    $previous_middlewares = self::$current_middlewares;

    self::$current_layout = $layout;
    self::$current_middlewares = self::mergeMiddlewares(self::$current_middlewares, $middlewares);

    $callback();

    self::$current_layout = $previous_layout;
    self::$current_middlewares = $previous_middlewares;
  }

  /**
   * Register a group of routes with both a prefix and a layout, and optional middlewares.
   *
   * @param string $prefix Prefix to apply (e.g. "/admin").
   * @param string $layout Layout FQCN to apply.
   * @param callable $callback Callback that will register routes inside this group.
   * @param array{before?: array, afterRender?: array, afterResponse?: array} $middlewares Group middlewares to accumulate.
   *
   * @return void
   */
  public static function group(string $prefix, string $layout, callable $callback, array $middlewares = []): void {
    $previous_prefix = self::$current_prefix;
    $previous_layout = self::$current_layout;
    $previous_middlewares = self::$current_middlewares;

    self::$current_prefix = $prefix;
    self::$current_layout = $layout;
    self::$current_middlewares = self::mergeMiddlewares(self::$current_middlewares, $middlewares);

    $callback();

    self::$current_prefix = $previous_prefix;
    self::$current_layout = $previous_layout;
    self::$current_middlewares = $previous_middlewares;
  }

  /**
   * Normalize a middleware array to the canonical shape.
   *
   * @param array{before?: array, afterRender?: array, afterResponse?: array} $middlewares Raw middleware definition.
   *
   * @return array{before: array, afterRender: array, afterResponse: array} Normalized middleware lists.
   */
  private static function normalizeMiddlewares(array $middlewares): array {
    return [
      'before'        => array_key_exists('before', $middlewares)        ? (array)$middlewares['before']        : [],
      'afterRender'   => array_key_exists('afterRender', $middlewares)   ? (array)$middlewares['afterRender']   : [],
      'afterResponse' => array_key_exists('afterResponse', $middlewares) ? (array)$middlewares['afterResponse'] : []
    ];
  }

  /**
   * Merge two middleware arrays (group + route).
   *
   * @param array{before: array, afterRender: array, afterResponse: array} $a Accumulated middlewares (group).
   * @param array{before?: array, afterRender?: array, afterResponse?: array} $b Route/group extra middlewares.
   *
   * @return array{before: array, afterRender: array, afterResponse: array} Merged middleware lists.
   */
  private static function mergeMiddlewares(array $a, array $b): array {
    $b = self::normalizeMiddlewares($b);

    return [
      'before'        => array_merge($a['before'],        $b['before']),
      'afterRender'   => array_merge($a['afterRender'],   $b['afterRender']),
      'afterResponse' => array_merge($a['afterResponse'], $b['afterResponse'])
    ];
  }
}
