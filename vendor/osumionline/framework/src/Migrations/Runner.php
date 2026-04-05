<?php declare(strict_types=1);

namespace Osumi\Framework\Migrations;

use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Osumi\Framework\Migrations\Context\MigrationContext;
use Osumi\Framework\Migrations\Contract\MigrationStepInterface;
use Osumi\Framework\Migrations\State\StateStore;
use Osumi\Framework\Migrations\Util\FilePatcher;
use Osumi\Framework\Migrations\Util\GitStatus;

final class Runner {
  /**
   * @param array{
   *   dryRun?: bool,
   *   force?: bool,
   *   verbose?: bool,
   *   io?: IOInterface,
   *   extra?: array<string, mixed>
   * } $opts
   */
  public static function run(string $project_root, string $from, string $to, array $opts = []): void {
    $io = $opts['io'] ?? new NullIO();

    $dry_run = (bool)($opts['dryRun'] ?? false);
    $force   = (bool)($opts['force'] ?? false);
    $verbose = (bool)($opts['verbose'] ?? false);

    $patcher = new FilePatcher(
      project_root: $project_root,
      io: $io,
      dry_run: $dry_run,
      verbose: $verbose
    );

    $state_store = new StateStore($project_root);

    $ctx = new MigrationContext(
      project_root: $project_root,
      io: $io,
      dry_run: $dry_run,
      force: $force,
      verbose: $verbose,
      patcher: $patcher,
      state_store: $state_store
    );

    $manifest = self::loadManifest();
    $steps = self::selectSteps($manifest, $from, $to);

    if (count($steps) === 0) {
      return;
    }

    GitStatus::ensureCleanWorkingTree(
      project_root: $project_root,
      force: $force,
      io: $io,
      verbose: $verbose
    );

    $io->write(sprintf('[OFW] Running migrations (%s -> %s)%s', $from, $to, $dry_run ? ' [dry-run]' : ''));

    foreach ($steps as $step) {
      $io->write(sprintf('[OFW] - %s: %s', $step->getVersion(), $step->getDescription()));
      $step->apply($ctx);
    }

    if (!$dry_run) {
      // Note: for now it's stored from project root; later we will move it to ofw_tmp.
      $state_store->writeLastMigrated($to);
    }

    $io->write('[OFW] Done.');
  }

  /**
   * @return array<int, array{since: string, step: class-string<MigrationStepInterface>}>
   */
  private static function loadManifest(): array {
    /** @var array<int, array{since: string, step: class-string<MigrationStepInterface>}> $manifest */
    $manifest = require __DIR__ . '/manifest.php';

    usort(
      $manifest,
      static fn(array $a, array $b): int => version_compare($a['since'], $b['since'])
    );

    return $manifest;
  }

  /**
   * @param array<int, array{since: string, step: class-string<MigrationStepInterface>}> $manifest
   * @return array<int, MigrationStepInterface>
   */
  private static function selectSteps(array $manifest, string $from, string $to): array {
    $out = [];

    foreach ($manifest as $entry) {
      $since = $entry['since'];

      // run if from < since <= to
      if (version_compare($from, $since, '<') && version_compare($to, $since, '>=')) {
        $class_name = $entry['step'];
        $out[] = new $class_name();
      }
    }

    return $out;
  }
}
