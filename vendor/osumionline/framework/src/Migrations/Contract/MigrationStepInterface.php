<?php declare(strict_types=1);

namespace Osumi\Framework\Migrations\Contract;

use Osumi\Framework\Migrations\Context\MigrationContext;

interface MigrationStepInterface {
  public function getVersion(): string;

  public function getDescription(): string;

  /**
   * Apply the migration step.
   *
   * Steps must be idempotent.
   *
   * @throws \RuntimeException
   */
  public function apply(MigrationContext $ctx): void;
}
