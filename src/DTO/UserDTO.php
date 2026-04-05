<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\App\DTO;

use Osumi\OsumiFramework\DTO\ODTO;
use Osumi\OsumiFramework\DTO\ODTOField;

class UserDTO extends ODTO{
	#[ODTOField(required: true)]
	public ?int $id_user = null;

	#[ODTOField(required: true, middleware: 'User', middlewareProperty: 'name')]
	public ?string $username = null;

	#[ODTOField(required: true, header: 'Host')]
	public ?string $host = null;
}
