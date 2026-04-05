<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\Task;

use Osumi\OsumiFramework\Core\OTask;
use Osumi\OsumiFramework\Tools\OTools;
use Osumi\OsumiFramework\Plugins\WebsocketServerCore;

/**
 * Start websocket server
 */
class WebsocketTask extends OTask {
	public function __toString() {
		return $this->getColors()->getColoredString('websocket', 'light_green').': '.OTools::getMessage('TASK_WEBSOCKET');
	}

	/**
	 * Run the task
	 *
	 * @return void Echoes framework information
	 */
	public function run(): void {
		$websocket_server_core = new WebsocketServerCore();
		$websocket_server_core->run();
	}
}
