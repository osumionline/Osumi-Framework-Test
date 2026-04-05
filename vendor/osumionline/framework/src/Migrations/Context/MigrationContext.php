<?php declare(strict_types=1);

namespace Osumi\Framework\Migrations\Context;

use Composer\IO\IOInterface;
use Osumi\Framework\Migrations\State\StateStore;
use Osumi\Framework\Migrations\Util\FilePatcher;

final class MigrationContext {
  public function __construct(
    public readonly string $project_root,
    public readonly IOInterface $io,
    public readonly bool $dry_run,
    public readonly bool $force,
    public readonly bool $verbose,
    public readonly FilePatcher $patcher,
    public readonly StateStore $state_store
  ) {}
}
