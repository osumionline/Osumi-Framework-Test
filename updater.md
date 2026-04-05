# Guion global: **OFW Plugin Updater**

## 0) Objetivo

*   **Automatizar migraciones** en proyectos que usan `osumionline/framework` cuando el usuario ejecuta `composer update`.
*   Mantener el **plugin** extremadamente **fino/agnóstico** para **no actualizarlo** en cada release del framework.
*   Toda la **lógica de migraciones** vive en el **core** (OFW).

***

## 1) Repositorios y paquetes

1.  **Core del framework**: `osumionline/framework`
    *   Contiene:
        *   `src/Migrations/Runner.php` (orquestador)
        *   `src/Migrations/Steps/*` (pasos versionados)
        *   `src/Migrations/manifest.(php|json)` (**lista de hitos/versiones** que requieren migración)
        *   `bin/ofw-migrate` (CLI manual)
        *   Helpers/utilidades de parcheo seguro (backups, cambios atómicos, etc.)

2.  **Plugin**: `osumionline/plugin-updater`
    *   Paquete **independiente** con `"type": "composer-plugin"`.
    *   Muy fino: solo detecta `from → to` para `osumionline/framework` y llama al Runner del core **tras el autoload**.
    *   No contiene pasos ni lógica de versiones.
    *   Opcional: depende de `composer/semver` si quieres hacer alguna validación simple (no necesaria si delegas todo al core).

3.  **Skeleton**: `osumionline/new`
    *   Requiere:
        *   `osumionline/framework` (p.ej. `^9.9`)
        *   `osumionline/plugin-updater` (p.ej. `^1`)
    *   (Opcional pero recomendable) Define en `config.allow-plugins` la activación del plugin para proyectos **nuevos**.

***

## 2) Responsabilidades

### Plugin (`osumionline/plugin-updater`)

*   **Eventos**: suscribirse a:
    *   `POST_PACKAGE_UPDATE`: detectar si se actualiza `osumionline/framework` y capturar `from/to`.
    *   `POST_AUTOLOAD_DUMP`: ejecutar la migración (ya con el autoload de la versión **nueva**).
*   **Acciones**:
    *   Resolver `projectRoot` (dirname de `vendor-dir`).
    *   Leer **opciones** del root package (`extra.ofw`) y **variables** (`OFW_DRY_RUN`, `OFW_FORCE`).
    *   **Invocar** `\Osumi\Framework\Migrations\Runner::run($projectRoot, $from, $to, $opts)`.
*   **No** define pasos ni manifiestos. **No** conoce releases futuras.

### Core (OFW)

*   Mantiene **toda la lógica**:
    *   **Manifest** de hitos/versiones con migración.
    *   **Runner** que interpreta `from/to` contra el manifest y decide qué `Steps` ejecutar.
    *   **Steps** idempotentes y seguros (cambios de config, renombres, etc.).
    *   **CLI** `bin/ofw-migrate` para ejecución manual (misma lógica que el Runner, sin depender del plugin).

***

## 3) Flujo de ejecución (automático y manual)

### Automático (con plugin permitido)

1.  Usuario ejecuta `composer update`.
2.  `POST_PACKAGE_UPDATE`: el plugin detecta actualización de `osumionline/framework` y guarda `from` y `to`.
3.  `POST_AUTOLOAD_DUMP`: el plugin llama al `Runner` del core con `from/to`.
4.  El Runner:
    *   Lee el **manifest** del core.
    *   Selecciona los `Steps` **cuyos hitos están entre `(from, to]`**.
    *   Ejecuta cada `Step` en orden (idempotentes; con backups).
5.  Mensajería `[OFW]` + salida limpia.

### Manual (fallback)

*   Si el plugin **no está permitido aún** (`allow-plugins`) o el usuario ejecutó `--no-plugins`:
    *   El usuario ejecuta:
        ```bash
        php vendor/bin/ofw-migrate --dry-run
        php vendor/bin/ofw-migrate
        ```
    *   El CLI llama al **mismo Runner**.
    *   **Detección de `from`** en manual:
        *   Recomendado: OFW guarda un estado local **cuando se ejecuta una migración** (p. ej. `.ofw/state.json` con `{"lastMigrated": "9.8.2"}`) y/o lee de `composer.lock` la versión actual instalada como aproximación.
        *   Opcionalmente acepta `--from=9.8.2 --to=9.9.0` para casos especiales.

***

## 4) Diseño del **manifest** (core)

Tu idea de “tabla de pares `9.8.2 → 9.9.0 -> true` / `9.9.0 → 9.9.1 -> false`” funciona, pero obliga a mantener **múltiples combinaciones**.

**Más mantenible (recomendado):** un **manifest por hitos** (target mínimo), y ejecutar cualquier hito `h` cuando `from < h ≤ to`. Así **no declaras falsos**; solo enumeras hitos que **sí** tienen migración.

`src/Migrations/manifest.php`

```php
<?php
// Solo se enumeran hitos que tienen migración
return [
  ['since' => '9.9.0', 'step' => \Osumi\Framework\Migrations\Steps\V9_9_0::class],
  ['since' => '10.0.0', 'step' => \Osumi\Framework\Migrations\Steps\V10_0_0::class],
  // ...
];
```

### Resolución en el Runner

*   Cargar manifest.
*   Ordenar por versión (ascendente).
*   Para cada entrada: si `from < since <= to` ⇒ instanciar y ejecutar el `Step`.
*   Sin hitos aplicables ⇒ **no decir nada** (como quieres) o un mensaje informativo mínimo.

> Si prefieres tu formato de **pares explícitos**, también es viable:  
> el Runner compone una lista de **segments** contiguos y ejecuta los `true`.  
> Pero el enfoque “por hito” evita crecimiento combinatorial.

***

## 5) `Runner` y `Steps` (core)

**Runner (`src/Migrations/Runner.php`)**:

*   API: `run(string $root, string $from, string $to, array $opts = []): void`
*   Usa `composer/semver` para comparar versiones.
*   Carga manifest, filtra hitos, ejecuta `Steps` en orden.
*   Opciones:
    *   `dryRun` (de `OFW_DRY_RUN` y/o parámetro).
    *   `force` (de `OFW_FORCE` para ignorar working tree sucia).
    *   `io` para logs (si lo llama el plugin).

**Steps (`src/Migrations/Steps/*`)**:

*   Clase por hito (p.ej. `V9_9_0`).
*   Deben ser **idempotentes**.
*   Helpers:
    *   `replaceInFile()` (con backups `.bak-YYYYmmddHHMMSS`).
    *   `writeJson()` atómico (escribir a temporal y renombrar).
    *   `ensureLineInEnv()` / `renameEnvKey()`.
    *   `safeMove()` / `safeCopy()`.
*   Evitar tocar `vendor/`. Operar en `config/`, `app/`, `.env`, etc.

***

## 6) CLI manual (`bin/ofw-migrate`)

*   Entrypoint PHP simple (shebang opcional) que inicializa autoload y llama a `Runner`.
*   Flags:
    *   `--dry-run`, `--force`, `--from=X`, `--to=Y`, `--verbose`.
*   **Obtención de `from`/`to`**:
    *   `to`: desde `composer.lock` (versión instalada de `osumionline/framework`).
    *   `from`: preferible desde `.ofw/state.json` (última migración aplicada).  
        Si no existe, permite `--from=` o asume `from = to` (no hace nada) e informa.

***

## 7) `allow-plugins` y plan de transición

*   En **9.9.0** introduces la dependencia al plugin en el **core**.
*   Si el proyecto **no** tiene permitido el plugin, **se instalará pero no se activará** en esa ejecución.
*   **Documenta** ejecutar **una sola vez**:
    ```bash
    composer config allow-plugins.osumionline/plugin-updater true
    # o global:
    composer global config allow-plugins.osumionline/plugin-updater true
    ```
*   Para esa **primera** actualización, el usuario puede ejecutar el **CLI manual**:
    ```bash
    php vendor/bin/ofw-migrate --dry-run
    php vendor/bin/ofw-migrate
    ```
*   A partir de entonces, las siguientes updates ya tendrán el plugin **permitido** y la migración será **automática**.

> Para **proyectos nuevos** (skeleton), incluye en `composer.json`:
>
> ```json
> {
>   "config": {
>     "allow-plugins": {
>       "osumionline/plugin-updater": true
>     }
>   }
> }
> ```
>
> Así no necesitan el paso manual.

***

## 8) Mensajería, DX y seguridad

*   Prefijo de logs: **`[OFW]`**.
*   Respetar:
    *   `--no-plugins` → no se ejecuta el plugin (explica el fallback).
    *   `--no-scripts` → no te afecta si no dependes de scripts del root.
*   `OFW_DRY_RUN=1` y `OFW_FORCE=1` como variables de entorno.
*   Comprobar **working tree** sucia (si hay `.git`) y pedir `--force` o abortar.
*   Escribir cambios con **backups** y operaciones **atómicas**.
*   **Fail-fast** (o configurable): si un Step falla, aborta y muestra instrucciones para recuperar con backups.

***

## 9) Testing y validación

*   **Fixtures** de proyectos ejemplo (con y sin personalizaciones).
*   **Matriz de versiones**: `from` en {9.8.0, 9.8.2, 9.8.5} → `to` en {9.9.0, 9.9.1}.
*   Tests de **idempotencia** (ejecutar dos veces no rompe).
*   Tests con **repo sucio** (debe exigir `--force`).
*   Tests con `--no-plugins` + ejecución manual.
*   Pruebas en **Windows/Mac/Linux** si tocas rutas.

***

## 10) Entregables y pasos de implementación

1.  **Plugin** `osumionline/plugin-updater`
    *   `composer.json` con `"type": "composer-plugin"` y `extra.class`.
    *   Clase `\Osumi\PluginUpdater\Plugin`:
        *   Suscribe a `POST_PACKAGE_UPDATE` y `POST_AUTOLOAD_DUMP`.
        *   Detecta `osumionline/framework` y guarda `from/to`.
        *   Llama al Runner del core.
    *   Logs `[OFW]`, respeta `OFW_DRY_RUN` / `OFW_FORCE`.

2.  **Core (OFW)**
    *   `src/Migrations/manifest.(php|json)` con hitos (p.ej. `9.9.0`).
    *   `src/Migrations/Runner.php` que ejecuta Steps si `from < since ≤ to`.
    *   `src/Migrations/Steps/V9_9_0.php` (ejemplo).
    *   `bin/ofw-migrate` (CLI manual).
    *   Utilidades de parcheo y backups.
    *   (Opcional) `.ofw/state.json` para persistir `lastMigrated`.

3.  **Framework `composer.json`**
    *   Añade:
        ```json
        "require": {
          "osumionline/plugin-updater": "^1"
        }
        ```
    *   Documenta `allow-plugins` y el uso del CLI manual.

4.  **Skeleton `osumionline/new`**
    *   Requiere el plugin y define `allow-plugins` (para proyectos nuevos).

5.  **Docs**
    *   `UPGRADE.md`: explica qué hace cada hito y cómo ejecutar `ofw-migrate`.
    *   `README` del plugin: permisos, eventos, mensajes esperables.

6.  **Plan de despliegue (9.9.0)**
    *   Publica OFW 9.9.0 con el **manifest** (si hay migración).
    *   Publica el **plugin**.
    *   Comunica a usuarios: `allow-plugins` + `vendor/bin/ofw-migrate` para esta primera vez.
    *   Siguientes releases: solo actualizar OFW (manifest + steps); **el plugin no cambia**.

***

## Mini‑snippets de referencia (muy resumidos)

**Plugin** (extracto):

```php
final class Plugin implements PluginInterface, EventSubscriberInterface {
  private ?array $ofwUpdate = null;
  public static function getSubscribedEvents(): array {
    return [
      PackageEvents::POST_PACKAGE_UPDATE => 'onPostPackageUpdate',
      ScriptEvents::POST_AUTOLOAD_DUMP  => 'onPostAutoloadDump',
    ];
  }
  public function onPostPackageUpdate(PackageEvent $e): void {
    $op = $e->getOperation();
    if ($op->getTargetPackage()->getName() !== 'osumionline/framework') return;
    $this->ofwUpdate = [
      'from' => $op->getInitialPackage()->getPrettyVersion(),
      'to'   => $op->getTargetPackage()->getPrettyVersion(),
    ];
  }
  public function onPostAutoloadDump(Event $e): void {
    if (!$this->ofwUpdate) return;
    $root = \dirname($this->composer->getConfig()->get('vendor-dir'));
    if (class_exists(\Osumi\Framework\Migrations\Runner::class)) {
      \Osumi\Framework\Migrations\Runner::run($root, $this->ofwUpdate['from'], $this->ofwUpdate['to'], [
        'dryRun' => (bool) getenv('OFW_DRY_RUN'),
        'force'  => (bool) getenv('OFW_FORCE'),
        'io'     => $this->io,
      ]);
    }
  }
}
```

**Runner** (idea clave):

```php
$manifest = include __DIR__.'/manifest.php'; // o json_decode(...)
usort($manifest, fn($a,$b) => version_compare($a['since'], $b['since']));
foreach ($manifest as $entry) {
  $since = $entry['since'];
  if (version_compare($from, $since, '<') && version_compare($to, $since, '>=')) {
    (new $entry$root, $io, $dryRun)->apply();
  }
}
```
