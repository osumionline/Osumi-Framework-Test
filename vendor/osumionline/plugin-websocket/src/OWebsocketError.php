<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\Plugins;

/**
 * OWebsocketError - List of standard websocket error codes
 */
enum OWebsocketError: string {
	case InvalidJson = 'invalid_json';
	case BadRequest = 'bad_request';
	case UnknownAction = 'unknown_action';
	case Unauthorized = 'unauthorized';
	case InvalidResponse = 'invalid_response';
	case ServerError = 'server_error';
}
