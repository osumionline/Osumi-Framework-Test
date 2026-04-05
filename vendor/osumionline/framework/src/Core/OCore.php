<?php

declare(strict_types=1);

namespace Osumi\OsumiFramework\Core;

use Osumi\OsumiFramework\ORM\ODBContainer;
use Osumi\OsumiFramework\Cache\OCacheContainer;
use Osumi\OsumiFramework\Web\OSession;
use Osumi\OsumiFramework\Web\ORequest;
use Osumi\OsumiFramework\Routing\OUrl;
use Osumi\OsumiFramework\Tools\OTools;
use Osumi\OsumiFramework\Log\OLog;
use PDO;
use ReflectionParameter;
use Exception;
use Throwable;

/**
 * OCore
 *
 * Main framework bootstrap and HTTP execution entry point.
 *
 * Responsibilities:
 * - Load configuration and internal containers (DB, cache, session, translations)
 * - Load user-defined routes
 * - Execute a request using routing + component rendering + layouts
 * - In 9.9, execute middlewares in three phases: before / afterRender / afterResponse
 */
class OCore {
  /** @var ODBContainer|null Database container (can hold multiple connections). */
  public ?ODBContainer $db_container = null;

  /** @var OCacheContainer|null Cache container for cached objects. */
  public ?OCacheContainer $cache_container = null;

  /** @var OConfig|null Global framework configuration holder. */
  public ?OConfig $config = null;

  /** @var OSession|null Session wrapper for user session management. */
  public ?OSession $session = null;

  /** @var OTranslate|null Translation loader for framework/app messages. */
  public ?OTranslate $translate = null;

  /** @var float|null Application start time in milliseconds. */
  public ?float $start_time = null;

  /** @var array Array of loaded services, to use them as singletons. */
  public array $services = [];

  /** @var array Array of CSS/JS files to be included in the response. */
  public array $includes = [
		'css' => [],
		'inline_css' => [],
		'js' => [],
		'inline_js' => []
	];

  /**
   * Return types map used to build "Content-Type" header.
   *
   * @var array<string, string>
   */
  private array $return_types = [
    'html' => 'text/html; charset=utf-8',
    'json' => 'application/json; charset=utf-8',
    'xml'  => 'application/xml; charset=utf-8',
    'txt'  => 'text/plain; charset=utf-8'
  ];

  /** @var int Current HTTP status code. */
  private int $http_status = 200;

	/**
	 * Get the start time in milliseconds to use in benchmarks
	 */
	public function __construct() {
		$this->start_time = microtime(true);
	}

	/**
	 * Get whole projects base dir
	 *
	 * @return string Absolute path of the project
	 */
	private function getBaseDir(): string {
		// Start from the directory of the executed script
		$dir = dirname(__DIR__, 3);

		// Look for a marker file or directory that indicates the project root
		while (!is_dir($dir . '/vendor') && $dir !== '/') {
			$dir = dirname($dir);
		}

		// If we've reached the filesystem root without finding our marker, throw an exception
		if ($dir === '/') {
			throw new \RuntimeException("Could not locate project root directory");
		}

		return $dir . '/';
	}

  /**
   * Load framework configuration and initialize services.
   *
   * @param bool $from_cli When true, avoids session and HTTP-specific initialization.
   *
   * @return void
   */
  public function load(bool $from_cli = false): void {
		date_default_timezone_set('Europe/Madrid');
    $this->config = new OConfig($this->getBaseDir());

    // Due to a circular dependancy, check name of the log file after core loading
    if (is_null($this->config->getLog('name'))) {
      $this->config->setLog('name', OTools::slugify($this->config->getName()));
    }

    // Load framework translations
    $this->translate = new OTranslate();
    $this->translate->load($this->config->getDir('ofw_locale') . $this->config->getLang() . '.po');

    // If there is a DB connection configured, check drivers and load required classes
    if (
      $this->config->getDB('user') !== '' ||
      $this->config->getDB('pass') !== '' ||
      $this->config->getDB('host') !== '' ||
      $this->config->getDB('name') !== ''
    ) {
      $pdo_drivers = PDO::getAvailableDrivers();
      if (!in_array($this->config->getDB('driver'), $pdo_drivers)) {
        echo "ERROR: El sistema no dispone del driver " . $this->config->getDB('driver') . " solicitado para realizar la conexión a la base de datos.\n";
        exit;
      }

      $this->db_container = new ODBContainer();
    }

    if (!$from_cli) {
      session_start();
      $this->session = new OSession();
      $this->config->setUseSession(true);
    } else {
      $this->config->setUseSession(false);
    }

    // Set up an empty cache container
    $this->cache_container = new OCacheContainer();

    // Load routes
    $routes_path = $this->config->getDir('app_routes');
    $files = scandir($routes_path);
    foreach ($files as $file) {
      if ($file === '.' || $file === '..') {
        continue;
      }
      require_once $routes_path . $file;
    }

    // Load global middlewares (project-level)
    $middlewares_file = $this->config->getDir('app_middleware') . 'Middlewares.php';
    if (file_exists($middlewares_file)) {
      require_once $middlewares_file;
    }

    // Load global functions
    require_once $this->config->getDir('ofw_tools') . 'functions.php';
  }

  /**
   * Execute the current HTTP request.
   *
   * This method:
   * - Matches the route
   * - Runs middleware phases (before / afterRender / afterResponse)
   * - Renders the component and layout
   * - Emits headers and response body
   *
   * @return void
   */
  public function run(): void {
    if ($this->config->getAllowCrossOrigin()) {
      header('Access-Control-Allow-Origin: *');
      header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
      header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    }

    // Load current URL
    $u = new OUrl($_SERVER['REQUEST_METHOD']);
    $u->setCheckUrl($_SERVER['REQUEST_URI'], $_GET, $_POST, $_FILES);
    $url_result = $u->process();

    if (!$url_result['res']) {
      $this->setHttpStatus(404);
	  $this->closeDbConnections();
      OTools::showErrorPage($url_result, '404');
      return;
    }

    // If the call method is OPTIONS, just return OK right away
    if ($url_result['method'] === 'OPTIONS') {
      header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
      $this->closeDbConnections();
      return;
    }

    // Check method
    if ($url_result['method'] !== $url_result['component_method']) {
      $url_result['message'] = OTools::getMessage('ERROR_405_MESSAGE', [$url_result['component_method'], $url_result['method']]);
      $this->setHttpStatus(405);
      $this->closeDbConnections();
	  OTools::showErrorPage($url_result, '405');
      return;
    }

    // ---- Middleware pipeline begins ----
    OMiddleware::reset();
    OMiddleware::setRoute($url_result['middlewares']);

    // Determine expected response type (json/html/xml) from template extension
    $expected_type = $this->getExpectedResponseType($url_result);

    /** @var array<string, mixed> $mw_data */
    $mw_data = [
      'route'         => $url_result,
      'params'        => $url_result['params'],
      'headers'       => $url_result['headers'],
      'expected_type' => $expected_type
    ];

    // BEFORE
    $before_res = OMiddleware::runPhase('before', $mw_data);
    if ($before_res['stop'] === true) {
      $final = $this->buildErrorBody($expected_type, (int)$before_res['status_code'], (string)$before_res['message']);
      OMiddleware::setFinalBody($final);
      OMiddleware::setStatusCode((int)$before_res['status_code']);

      // Always run AFTER RESPONSE even after stop
      $this->prepareDefaultHeaders($expected_type);
      OMiddleware::runPhase('afterResponse', $mw_data);

      $this->emitMiddlewareResponse();
      $this->closeDbConnections();
      return;
    }

    // Execute component / view and render its body
    $return_type = $expected_type;
    $body = '';

    if (!$url_result['is_view']) {
      $component_instance = new $url_result['component']();

      // Build request (includes middleware access methods)
      $req = new ORequest($url_result);

      // Prepare parameter for component->run()
      $reflection_param = new ReflectionParameter([$component_instance, 'run'], 0);
      $reflection_param_type = $reflection_param->getType()->getName();

      if (str_starts_with($reflection_param_type, 'Osumi\OsumiFramework\App\DTO')) {
        $param = new $reflection_param_type($req);
      } else {
        $param = $req;
      }

      $body = $component_instance->render($param);
      $return_type = (string)$component_instance->component_info['template_type'];
    } else {
      $view_file = $this->config->getDir('app') . $url_result['component'];
      if (file_exists($view_file)) {
        $body = (string)file_get_contents($view_file);
        $return_type = (string)pathinfo($view_file, PATHINFO_EXTENSION);
      } else {
        $url_result['message'] = OTools::getMessage('ERROR_VIEW_MESSAGE', [$url_result['component']]);
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
        $this->closeDbConnections();
		OTools::showErrorPage($url_result, 'view');
        return;
      }
    }

    // Store component body for afterRender middlewares
    OMiddleware::setComponentBody($body);

    // AFTER RENDER
    $mw_data['component_body'] = OMiddleware::getComponentBody();
    $after_render_res = OMiddleware::runPhase('afterRender', $mw_data);

    if ($after_render_res['stop'] === true) {
      $final = $this->buildErrorBody($return_type, (int)$after_render_res['status_code'], (string)$after_render_res['message']);
      OMiddleware::setFinalBody($final);
      OMiddleware::setStatusCode((int)$after_render_res['status_code']);

      $this->prepareDefaultHeaders($return_type);
      OMiddleware::runPhase('afterResponse', $mw_data);

      $this->emitMiddlewareResponse();
      $this->closeDbConnections();
      return;
    }

    // Use potentially modified body after afterRender
    $body = OMiddleware::getComponentBody();

    // Layout render (wrap body)
    if (!is_null($url_result['layout'])) {
      $layout_instance = new $url_result['layout']();
      $layout_instance->title = $this->config->getDefaultTitle();
      $layout_instance->body = $body;

      $layout_body = $layout_instance->render();

      if (stripos($layout_body, '<html>') !== false) {
        $layout_body = str_ireplace('<html>', '<html lang="' . $this->config->getLang() . '">', $layout_body);
      }

      if (stripos($layout_body, '</head>') !== false) {
        $layout_body = str_ireplace('</head>', $this->renderInline() . '</head>', $layout_body);
        $layout_body = str_ireplace('</head>', $this->renderExternal() . '</head>', $layout_body);
      }

      $body = $layout_body;
    }

    OMiddleware::setFinalBody($body);

    // Prepare default headers before AFTER RESPONSE (middlewares may override)
    $this->prepareDefaultHeaders($return_type);

    // AFTER RESPONSE (always)
    $mw_data['final_body'] = OMiddleware::getFinalBody();
    OMiddleware::runPhase('afterResponse', $mw_data);

    $this->emitMiddlewareResponse();
    $this->closeDbConnections();
  }

  /**
   * Set the HTTP status code to be sent.
   *
   * @param int $status HTTP status code (e.g. 200, 404, 500).
   *
   * @return void
   */
  public function setHttpStatus(int $status): void {
    $this->http_status = $status;
  }

  /**
	 * Gets full HTTP status
	 *
	 * @return string Fullt HTTP status string
	 */
	public function getHttpStatus(): string {
		switch ($this->http_status) {
			case 200:
				return '200 OK';
				break;
			case 201:
				return '201 Created';
				break;
			case 400:
				return '400 Bad Request';
				break;
			case 403:
				return '403 Forbidden';
				break;
			case 404:
				return '404 Not Found';
				break;
			case 405:
				return '405 Method Not Allowed';
				break;
			case 409:
				return '409 Conflict';
				break;
			case 500:
				return '500 Internal Server Error';
				break;
		}
		return '200 OK';
	}

  /**
   * Determine expected response type from the route template extension.
   *
   * For views: based on file extension.
   * For components: instantiate the component and read component_info['template_type'].
   *
   * @param array<string, mixed> $url_result Matched route result.
   *
   * @return string Response type key (html/json/xml/txt).
   */
  private function getExpectedResponseType(array $url_result): string {
    if ((bool)$url_result['is_view'] === true) {
      $view_file = $this->config->getDir('app') . (string)$url_result['component'];
      $ext = (string)pathinfo($view_file, PATHINFO_EXTENSION);
      return $ext !== '' ? $ext : 'html';
    }

    $component = (string)$url_result['component'];
    $instance = new $component();
    if (property_exists($instance, 'component_info') && is_array($instance->component_info) && array_key_exists('template_type', $instance->component_info)) {
      return (string)$instance->component_info['template_type'];
    }

    return 'html';
  }

  /**
   * Build an error body from the framework error templates.
   *
   * Templates are searched in /Assets/template as:
   * - error.json
   * - error.html
   * - error.xml
   *
   * @param string $type Expected output type (html/json/xml).
   * @param int $status_code HTTP status code.
   * @param string $message Error message.
   *
   * @return string Rendered error body.
   */
  private function buildErrorBody(string $type, int $status_code, string $message): string {
    $type = array_key_exists($type, $this->return_types) ? $type : 'html';

    $error_template = $this->config->getDir('ofw_template') . 'error.' . $type;
    if (!file_exists($error_template)) {
      // Minimal fallback
      if ($type === 'json') {
        return (string)json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
      }
      return '<h1>Error ' . $status_code . '</h1><p>' . htmlspecialchars($message) . '</p>';
    }

    $tpl = (string)file_get_contents($error_template);
    $tpl = str_replace('{{ status_code }}', (string)$status_code, $tpl);
    $tpl = str_replace('{{ message }}', $message, $tpl);

    return $tpl;
  }

  /**
   * Set default headers in OMiddleware before afterResponse runs.
   *
   * This ensures consistent headers even when the pipeline stopped early.
   *
   * @param string $type Response type (html/json/xml/txt).
   *
   * @return void
   */
  private function prepareDefaultHeaders(string $type): void {
    $type = array_key_exists($type, $this->return_types) ? $type : 'html';

    if ($type !== 'html') {
      OMiddleware::setHeader('Cache-Control', 'no-cache, must-revalidate');
      OMiddleware::setHeader('Expires', 'Thu, 02 Jul 1981 03:00:00 GMT');
    }

    OMiddleware::setHeader('Content-type', $this->return_types[$type]);
    OMiddleware::setHeader('X-Powered-By', 'Osumi Framework ' . OTools::getVersion());
  }

  /**
   * Emit the response built by the middleware pipeline.
   *
   * @return void
   */
  private function emitMiddlewareResponse(): void {
    $status = OMiddleware::isError() ? OMiddleware::getErrorStatusCode() : OMiddleware::getStatusCode();
    header($_SERVER['SERVER_PROTOCOL'] . ' ' . $status);

    foreach (OMiddleware::getHeaders() as $name => $value) {
      header($name . ': ' . $value);
    }

    echo OMiddleware::getFinalBody();
  }

  /**
   * Close all DB connections if DB container is enabled.
   *
   * @return void
   */
  private function closeDbConnections(): void {
    if (!is_null($this->db_container)) {
      $this->db_container->closeAllConnections();
    }
  }

  /**
   * Returns inline content (CSS and JS).
   *
   * @return string Inline content (CSS/JS tags) if any are configured.
   */
  private function renderInline(): string {
    $ret = '';

    // Add global CSS files
	if (count($this->config->getCssList())) {
		foreach ($this->config->getCssList() as $css) {
      $css_file = $this->config->getDir('public') . 'css/' . $css . '.css';
      if (file_exists($css_file)) {
        $ret .= "<link rel=\"stylesheet\" href=\"/css/" . $css . ".css\">\n";
      }
		}
	}
	// Add global JS files
	if (count($this->config->getJsList())) {
		foreach ($this->config->getJsList() as $js) {
      $js_file = $this->config->getDir('public') . 'js/' . $js . '.js';
      if (file_exists($js_file)) {
        $ret .= "<script type=\"text/javascript\" src=\"/js/" . $js . ".js\"></script>\n";
      }
		}
	}

	// Process inline CSS files
	if (count($this->includes['inline_css']) > 0) {
		foreach ($this->includes['inline_css'] as $css) {
			if (file_exists($css)) {
				$ret .= "<style>\n";
				$ret .= file_get_contents($css);
				$ret .= "\n</style>\n";
			} else {
				throw new Exception("No valid inline CSS file found: " . $css);
			}
		}
	}
	// Process inline JS files
	if (count($this->includes['inline_js']) > 0) {
		foreach ($this->includes['inline_js'] as $js) {
			if (file_exists($js)) {
				$ret .= "<script>\n";
				$ret .= file_get_contents($js);
				$ret .= "\n</script>\n";
			} else {
				throw new Exception("No valid inline JS file found for the component: " . $js);
			}
		}
	}

    return $ret;
  }

  /**
   * Returns external content (CSS and JS).
   *
   * @return string External content (link/script tags) if any are configured.
   */
  private function renderExternal(): string {
    $ret = '';

	// Add head elements defined in config
	if (count($this->config->getHeadElements())) {
		$ret .= $this->buildHeadElements($this->config->getHeadElements());
	}
	// Process CSS files
	if (count($this->includes['css']) > 0) {
		foreach ($this->includes['css'] as $css) {
			$ret .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $css . "\">\n";
		}
	}
	// Process JS files
	if (count($this->includes['js']) > 0) {
		foreach ($this->includes['js'] as $js) {
			$ret .= "<script src=\"" . $js . "\"></script>\n";
		}
	}

    return $ret;
  }

  /**
	 * Build HTML elements for the <head> from an array of definitions.
	 *
	 * Each element of the array must be another array with the keys:
	 * - 'item' => tag name (e.g. 'meta', 'link', 'script')
	 * - 'attributes' => associative array of attributes (e.g. ['rel'=>'icon','href'=>'...'])
	 *
	 * For 'script' tags a full opening and closing tag will be generated
	 * (<script ...></script>), while other tags will be self-closed (<meta ... />).
	 *
	 * @param array $items Array of element definitions
	 *
	 * @return string Concatenated elements separated by "\n"
	 */
	public function buildHeadElements(array $items): string {
		$ret = [];
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$tag = isset($item['item']) ? strtolower((string)$item['item']) : '';
			if ($tag === '') {
				continue;
			}
			$attrs = isset($item['attributes']) && is_array($item['attributes']) ? $item['attributes'] : [];
			$parts = [];
			foreach ($attrs as $k => $v) {
				if ($v === true) {
					$parts[] = $k;
				} elseif ($v === false || is_null($v)) {
					continue;
				} else {
					$parts[] = $k . '="' . htmlspecialchars((string)$v, ENT_QUOTES) . '"';
				}
			}
			$attr_str = count($parts) ? ' ' . implode(' ', $parts) : '';
			if ($tag === 'script') {
				$ret[] = "<script" . $attr_str . "></script>";
			} else {
				$ret[] = "<" . $tag . $attr_str . " />";
			}
		}
		return implode("\n", $ret);
	}

  /**
	 * Custom error handler, shows an error page and the error's stack trace
	 *
	 * @param Throwable $ex Given error
	 *
	 * @return void
	 */
	public function errorHandler(Throwable $ex): void {
		$log = new OLog(get_class($this));
		$params = ['message' => OTools::getMessage('ERROR_500_LABEL')];
		$params['message'] = "<strong>Error:</strong> \"" . $ex->getMessage() . "\"\n<strong>File:</strong> \"" . $ex->getFile() . "\" (Line: " . $ex->getLine() . ")\n\n<strong>Trace:</strong> \n";
		foreach ($ex->getTrace() as $trace) {
			if (array_key_exists('file', $trace)) {
				$params['message'] .= "  <strong>File:</strong> \"" . $trace['file'] . " (Line: " . $trace['line'] . ")\"\n";
			}
			if (array_key_exists('class', $trace)) {
				$params['message'] .= "  <strong>Class:</strong> \"" . $trace['class'] . "\"\n";
			}
			if (array_key_exists('function', $trace)) {
				$params['message'] .= "  <strong>Function:</strong> \"" . $trace['function'] . "\"\n\n";
			}
		}
		$log->error(str_ireplace('</strong>', '', str_ireplace('<strong>', '', $params['message'])));
		$this->setHttpStatus(500);
		OTools::showErrorPage($params, '500');
	}
}
