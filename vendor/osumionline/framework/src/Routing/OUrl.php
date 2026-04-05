<?php

declare(strict_types=1);

namespace Osumi\OsumiFramework\Routing;

use Osumi\OsumiFramework\Core\OConfig;

/**
 * OUrl
 *
 * URL matcher and routing helper.
 *
 * Responsibilities:
 * - Load registered routes from ORoute::$routes
 * - Match current request URL and method against those routes
 * - Extract dynamic parameters from URL patterns
 * - Combine extracted route params with GET/POST/FILES/JSON body parameters
 * - Provide helper functions to generate URLs and perform redirects
 */
class OUrl {
  /** @var OConfig|null Framework config instance used to locate app directories and settings. */
  private OConfig | null $config = null;

  /**
   * Registered routes loaded from ORoute::$routes.
   *
   * @var array<int, array<string, mixed>>|null
   */
  private array | null $urls = null;

  /** @var string URL path to be checked (query string removed). */
  private string $check_url = '';

  /**
   * Unified parameter bag (route params + GET + POST + FILES + JSON input).
   *
   * @var array<string, mixed>
   */
  private array $url_params = [];

  /** @var string HTTP method used for matching (uppercase). */
  private string $method = '';

  /**
   * Build a matcher for the given HTTP method.
   *
   * @param string $method HTTP method used to access the URL (GET/POST/PUT/DELETE/OPTIONS).
   *
   * @return void
   */
  public function __construct(string $method) {
    global $core;

    $this->config = $core->config;
    $this->method = strtoupper($method);
    $this->urls = ORoute::$routes;
  }

  /**
   * Set the URL to be checked and load all passed parameters.
   *
   * This merges:
   * - $_GET
   * - $_POST
   * - $_FILES
   * - JSON body (php://input)
   *
   * @param string $check_url URL to be checked (can include query string).
   * @param array<string, mixed>|null $get GET parameters.
   * @param array<string, mixed>|null $post POST parameters.
   * @param array<string, mixed>|null $files Uploaded files parameters (multipart/form-data).
   *
   * @return void
   */
  public function setCheckUrl(string $check_url, array | null $get = null, array | null $post = null, array | null $files = null): void {
    $this->check_url = $check_url;

    $check_params = stripos($check_url, '?');
    if ($check_params !== false) {
      $this->check_url = substr($check_url, 0, $check_params);
    }

    if (!is_null($get)) {
      foreach ($get as $key => $value) {
        $this->url_params[(string)$key] = $value;
      }
    }

    if (!is_null($post)) {
      foreach ($post as $key => $value) {
        $this->url_params[(string)$key] = $value;
      }
    }

    if (!is_null($files)) {
      foreach ($files as $key => $value) {
        $this->url_params[(string)$key] = $value;
      }
    }

    /** @var mixed $input */
    $input = json_decode((string)file_get_contents('php://input'), true);
    if (!is_null($input) && is_array($input)) {
      foreach ($input as $key => $value) {
        $this->url_params[(string)$key] = $value;
      }
    }
  }

  /**
   * Match the given URL against the registered routes and return its configuration.
   *
   * The returned array is consumed by OCore::run() to execute the matched component/view.
   *
   * @param string|null $url Optional override URL to be checked (used mostly for tests/tools).
   *
   * @return array{
   *   component: string|null,
   *   middlewares: array{before: array, afterRender: array, afterResponse: array},
   *   layout: string|null,
   *   type: string,
   *   params: array<string, mixed>,
   *   headers: array<string, string>,
   *   method: string,
   *   component_method: string,
   *   is_view: bool,
   *   res: bool
   * } Matched route information (res=false when not found).
   */
  public function process(string | null $url = null): array {
    if (!is_null($url)) {
      $this->check_url = $url;
    }

    $found = false;
    $i = 0;

    $ret = [
      'component' => null,
      'middlewares' => ['before' => [], 'afterRender' => [], 'afterResponse' => []],
      'layout' => null,
      'type' => 'html',
      'params' => [],
      'headers' => getallheaders(),
      'method' => $this->method,
      'component_method' => '',
      'is_view' => false,
      'res' => false
    ];

    if (is_null($this->urls)) {
      return $ret;
    }

    while (!$found && $i < count($this->urls)) {
      $route = new ORouteCheck((string)$this->urls[$i]['url']);
      $chk = $route->matchesUrl($this->check_url);

      if (!is_null($chk)) {
        $found = true;

        $ret['res'] = true;
        $ret['component'] = (string)$this->urls[$i]['component'];
        $ret['component_method'] = (string)$this->urls[$i]['method'];
        $ret['is_view'] = (bool)$this->urls[$i]['is_view'];

        if (array_key_exists('middlewares', $this->urls[$i])) {
          /** @var array{before: array, afterRender: array, afterResponse: array} $mws */
          $mws = (array)$this->urls[$i]['middlewares'];
          $ret['middlewares'] = $mws;
        }

        if (array_key_exists('layout', $this->urls[$i])) {
          $ret['layout'] = $this->urls[$i]['layout'];
        }

        /** @var array<string, mixed> $chk */
        $ret['params'] = $chk;

        foreach ($this->url_params as $key => $value) {
          $ret['params'][(string)$key] = $value;
        }
      }

      $i++;
    }

    return $ret;
  }

  /**
   * Generate a URL for a configured route by component name.
   *
   * @param string $component Component class short name to match (e.g. "UserProfileComponent").
   * @param array<string, string|int|float> $params Parameters to replace dynamic segments (":id", ":slug"...).
   * @param bool $absolute Whether to return an absolute URL (base + path).
   *
   * @return string Generated URL (empty string if route not found).
   */
  public static function generateUrl(string $component, array $params = [], bool $absolute = false): string {
    global $core;

    $found = false;
    $i = 0;
    $url = '';
    $routes = ORoute::$routes;

    while (!$found && $i < count($routes)) {
      $check_component = (string)$routes[$i]['component'];
      $check_component_parts = explode('\\', $check_component);
      $check_last_part = array_pop($check_component_parts);

      if ($check_last_part === $component) {
        $url = (string)$routes[$i]['url'];
        $found = true;
      }
      $i++;
    }

    if ($found) {
      foreach ($params as $key => $value) {
        $url = str_replace(':' . $key, (string)$value, $url);
      }
    }

    if ($absolute === true) {
      $base = $core->config->getUrl('base');
      $base = substr($base, 0, strlen($base) - 1);
      $url = $base . $url;
    }

    return $url;
  }

  /**
   * Redirect the user to a new URL using an HTTP redirect.
   *
   * @param string $url Target URL to redirect to.
   *
   * @return void
   */
  public static function goToUrl(string $url): void {
    header('Location:' . $url);
    exit;
  }
}
