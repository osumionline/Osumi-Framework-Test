<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\Plugins;

class WebsocketInstaller
{
    /**
     * Verifica si el framework está instalado y sugiere ejecutar el comando manualmente si no se encuentra.
     *
     * @return void
     */
    public static function runCheck(): void {
        $framework_dir = dirname(__DIR__, 2) . '/framework/';

        if (!is_dir($framework_dir)) {
            echo "\n[INFO] Osumi Framework no encontrado.\n";
            echo "Por favor, instala primero el framework y luego ejecuta:\n";
            echo "  composer install-plugin-websocket\n\n";
            return;
        }

        self::run();
    }

    /**
     * Instala el plugin copiando los archivos necesarios al framework.
     *
     * @return void
     */
    public static function run(): void {
        $framework_dir = dirname(__DIR__, 2) . '/framework/src/Task';
        $target_file = $framework_dir . 'WebsocketTask.php';
        $plugin_file = __DIR__ . '/../resources/WebsocketTask.php';

        if (!copy($plugin_file, $target_file)) {
            echo "[ERROR] No se pudo copiar el archivo WebsocketTask.php.\n";
            return;
        }

        echo "[SUCCESS] Plugin WebSocket instalado correctamente.\n";
    }
}
