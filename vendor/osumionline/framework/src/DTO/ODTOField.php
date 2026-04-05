<?php

declare(strict_types=1);

namespace Osumi\OsumiFramework\DTO;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ODTOField {
  /**
   * DTO field metadata.
   *
   * This attribute is used to describe how a DTO property should be validated and loaded.
   * Middlewares replace filters in 9.9: you can map a DTO property from middleware context.
   */
  public function __construct(
    /** @var bool Whether this field is mandatory for DTO validation. */
    public bool $required = false,

    /**
     * @var string|null Conditional requirement expression/name.
     * If provided, the field may become required depending on another value.
     */
    public string | null $requiredIf = null,

    /**
     * @var string|null Middleware name to read from (e.g. "Auth").
     * The middleware must have already executed in the "before" phase and published context.
     */
    public string | null $middleware = null,

    /**
     * @var string|null Key to extract from middleware context (e.g. "id").
     * This is used together with $middleware.
     */
    public string | null $middlewareProperty = null,

    /**
     * @var string|null Header name to read from (e.g. "Authorization").
     * Useful for values that come directly from HTTP headers.
     */
    public string | null $header = null
  ) {
  }
}
