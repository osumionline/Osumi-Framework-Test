<?php

declare(strict_types=1);

namespace Osumi\OsumiFramework\Web;

use Osumi\OsumiFramework\Core\OMiddleware;

/**
 * ORequest
 *
 * Container for a single HTTP request.
 * It exposes the request method, headers and route parameters, and provides
 * convenient access to middleware context (accumulated during pipeline execution).
 */
class ORequest {
  /** @var string|null HTTP method used in the request (GET/POST/PUT/DELETE...). */
  private string | null $method = null;

  /** @var array<string, string> HTTP headers received for this request. */
  private array $headers = [];

  /** @var array<string, mixed> Route parameters extracted from the matched URL. */
  private array $params = [];

  /**
   * Build a request object from the URL resolution result.
   *
   * @param array<string, mixed> $url_result Data produced by router matching (method/headers/params...).
   */
  public function __construct(array $url_result) {
    $this->setMethod((string)$url_result['method']);
    /** @var array<string, string> $headers */
    $headers = (array)$url_result['headers'];
    $this->setHeaders($headers);

    /** @var array<string, mixed> $params */
    $params = (array)$url_result['params'];
    $this->setParams($params);
  }

  /**
   * Set the HTTP method used in the request.
   *
   * @param string $method HTTP method used in the request (GET/POST/PUT/DELETE...).
   *
   * @return void
   */
  public function setMethod(string $method): void {
    $this->method = strtoupper($method);
  }

  /**
   * Get the HTTP method used in the request.
   *
   * @return string|null The request method, or null if not set.
   */
  public function getMethod(): string | null {
    return $this->method;
  }

  /**
   * Set the request headers.
   *
   * @param array<string, string> $headers Map of header name => header value.
   *
   * @return void
   */
  public function setHeaders(array $headers): void {
    $this->headers = $headers;
  }

  /**
   * Get all request headers.
   *
   * @return array<string, string> Map of header name => header value.
   */
  public function getHeaders(): array {
    return $this->headers;
  }

  /**
   * Get a single header value by name.
   *
   * @param string $name Header name (case-sensitive as stored).
   *
   * @return string|null Header value or null if missing.
   */
  public function getHeader(string $name): string | null {
    return array_key_exists($name, $this->headers) ? $this->headers[$name] : null;
  }

  /**
   * Set route parameters.
   *
   * @param array<string, mixed> $params Route params extracted from URL matching.
   *
   * @return void
   */
  public function setParams(array $params): void {
    $this->params = $params;
  }

  /**
   * Get all route parameters.
   *
   * @return array<string, mixed> Route params map.
   */
  public function getParams(): array {
    return $this->params;
  }

  /**
   * Get a route parameter as string.
   *
   * @param string $name Parameter name.
   *
   * @return string|null Parameter value cast to string or null if missing.
   */
  public function getParam(string $name): string | null {
    if (!array_key_exists($name, $this->params)) {
      return null;
    }
    return (string)$this->params[$name];
  }

  /**
	 * Get a specific parameter as an int
	 *
	 * @param string $key Key code of the value to be retrieved
	 *
	 * @param mixed $default Default value if key not found
	 *
	 * @return int | null Int value of the required parameter
	 */
	public function getParamInt(string $key, mixed $default = null): int | null {
		$param = $this->getParam($key, $default);
		return (!is_null($param) && $param !== 'null' && is_numeric($param)) ? intval($param) : null;
	}

  /**
	 * Get a specific parameter as a string
	 *
	 * @param string $key Key code of the value to be retrieved
	 *
	 * @param mixed $default Default value if key not found
	 *
	 * @return string | null String value of the required parameter
	 */
	public function getParamString(string $key, mixed $default = null): string | null {
		$param = $this->getParam($key, $default);
		return !is_null($param) ? strval($param) : null;
	}

  /**
	 * Get a specific parameter as a float
	 *
	 * @param string $key Key code of the value to be retrieved
	 *
	 * @param mixed $default Default value if key not found
	 *
	 * @return float | null Float value of the required parameter
	 */
	public function getParamFloat(string $key, mixed $default = null): float | null {
		$param = $this->getParam($key, $default);
		return (!is_null($param) && $param !== 'null' && is_numeric($param)) ? floatval($param) : null;
	}

	/**
	 * Get a specific parameter as a boolean
	 *
	 * @param string $key Key code of the value to be retrieved
	 *
	 * @param mixed $default Default value if key not found
	 *
	 * @return bool | null Boolean value of the required parameter
	 */
	public function getParamBool(string $key, mixed $default = null): bool | null {
		$param = $this->getParam($key, $default);
		return !is_null($param) ? filter_var($param, FILTER_VALIDATE_BOOLEAN) : null;
	}

  /**
   * Get the full context produced by a middleware.
   *
   * This reads from the global middleware accumulator (OMiddleware),
   * which stores context for the current request execution.
   *
   * @param string $middleware Middleware name (usually class short name without "Middleware", e.g. "Auth").
   *
   * @return array<string, mixed> Context values published by that middleware.
   */
  public function getMiddleware(string $middleware): array {
    return OMiddleware::getMiddlewareContext($middleware);
  }

  /**
   * Get a single middleware context value.
   *
   * @param string $middleware Middleware name (e.g. "Auth").
   *
   * @param string $key Context key inside middleware context (e.g. "id").
   *
   * @return mixed The context value or null if missing.
   */
  public function getMiddlewareValue(string $middleware, string $key): mixed {
    return OMiddleware::getContext($middleware, $key);
  }

  /**
   * Check if current execution is running in an error state (stop triggered).
   *
   * @return bool True if a middleware stopped the pipeline and we are building an error response.
   */
  public function isError(): bool {
    return OMiddleware::isError();
  }

  /**
   * Get the error message if the pipeline was stopped by a middleware.
   *
   * @return string Error message (empty string if no error).
   */
  public function getErrorMessage(): string {
    return OMiddleware::getErrorMessage();
  }

  /**
   * Get the HTTP status code associated with the middleware stop.
   *
   * @return int Status code (200 if no error).
   */
  public function getErrorStatusCode(): int {
    return OMiddleware::getErrorStatusCode();
  }
}
