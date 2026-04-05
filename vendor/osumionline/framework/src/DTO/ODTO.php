<?php

declare(strict_types=1);

namespace Osumi\OsumiFramework\DTO;

use ReflectionClass;
use ReflectionProperty;
use Osumi\OsumiFramework\Web\ORequest;

/**
 * ODTO
 *
 * Base DTO class used by user-defined DTOs.
 *
 * Responsabilities:
 * - Hydrate public DTO properties from the current request (params/body/files), from HTTP headers or from middleware context.
 * - Validate "required" and "requiredIf" constraints declared via ODTOField attributes.
 *
 * Notes:
 * - DTO properties are expected to be public.
 * - The hydration order is:
 *   1) middleware (if configured)
 *   2) header (if configured)
 *   3) request params/body/files (default)
 */
class ODTO {
  /**
   * Validation error list produced while building the DTO.
   *
   * Each entry is a human-readable error message.
   *
   * @var array<int, string>
   */
  private array $validation_errors = [];

  /**
   * Constructor that loads data from the request into the DTO instance.
   *
   * It scans all public properties, reads the ODTOField attribute metadata (if present),
   * assigns values from middleware/header/request, and finally applies validations.
   *
   * @param ORequest $req Request object containing params/headers and middleware context.
   */
  public function __construct(ORequest $req) {
    $reflection = new ReflectionClass($this);
    $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
    $field_values = [];

    foreach ($properties as $property) {
      $attributes = $property->getAttributes(ODTOField::class);

      foreach ($attributes as $attribute) {
        $field_definition = $attribute->newInstance();
        $property_name    = $property->getName();

        // 1) Get value from middleware context if defined
        if (!is_null($field_definition->middleware)) {
          $mw_values = $req->getMiddleware($field_definition->middleware);
          if (
              is_array($mw_values) &&
              !is_null($field_definition->middlewareProperty) &&
              array_key_exists($field_definition->middlewareProperty, $mw_values)
            ) {
              $this->$property_name         = $mw_values[$field_definition->middlewareProperty];
              $field_values[$property_name] = $mw_values[$field_definition->middlewareProperty];
              continue;
            }
        }

        // 2) Get value from HTTP header if defined
        if (!is_null($field_definition->header)) {
          $header_value = $req->getHeader($field_definition->header);
          $this->$property_name         = $header_value;
          $field_values[$property_name] = $header_value;
          continue;
        }

        // 3) Default source: request params/body/files
        $type = $property->getType()?->getName();
        $value = match ($type) {
          'int'    => $req->getParamInt($property_name),
          'float'  => $req->getParamFloat($property_name),
          'bool'   => $req->getParamBool($property_name),
          'string' => $req->getParamString($property_name),
          'array'  => $req->getParam($property_name),
          default  => null
        };

        $this->$property_name = $value;
        $field_values[$property_name] = $value;
      }
    }

    // "required" and "requiredIf" field validations
    foreach ($properties as $property) {
      $attributes = $property->getAttributes(ODTOField::class);
      foreach ($attributes as $attribute) {
        $field_definition = $attribute->newInstance();
        $property_name    = $property->getName();

        if ($field_definition->required && is_null($field_values[$property_name] ?? null)) {
          $this->validation_errors[] = "The property '{$property_name}' is required.";
        }

        if (!is_null($field_definition->requiredIf)) {
          $dependency = $field_definition->requiredIf;
          if (!is_null($field_values[$dependency] ?? null) && is_null($field_values[$property_name] ?? null)) {
            $this->validation_errors[] = "The property '{$property_name}' is required because '{$dependency}' is set.";
          }
        }
      }
    }
  }

  /**
   * Checks if the DTO is valid (no validation errors).
   *
   * @return bool True if the DTO has no validation errores, false otherwise.
   */
  public function isValid(): bool {
    return empty($this->validation_errors);
  }

  /**
   * Get the DTO validation errors.
   *
   * @return array Validation error message list.
   */
  public function getValidationErrors(): array {
    return $this->validation_errors;
  }
}
