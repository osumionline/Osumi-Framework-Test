# osumionline/plugin-websocket

Plugin para añadir soporte de servidor WebSocket a Osumi Framework.

Este plugin permite arrancar un servidor WebSocket independiente desde una tarea CLI de la aplicación, definir acciones personalizadas y asociarlas a componentes del framework.

## Características

- Servidor WebSocket integrado mediante Ratchet
- Arranque desde una tarea CLI creada por el usuario
- Asociación de acciones a componentes
- Acciones públicas y protegidas
- Validación de tokens configurable por el usuario
- Gestión de múltiples conexiones por usuario
- Métodos para envío directo y broadcast
- Sistema de depuración del estado interno

## Instalación

```bash
composer require osumionline/plugin-websocket

## Configuración

La configuración del plugin se define en el archivo:

```
/src/Config/Config.json
```

Dentro del bloque plugins:

```json
{
  "plugins": {
    "websocket": {
      "host": "localhost",
      "port": 8080,
      "path": "/ws"
    }
  }
}
```

## Opciones disponibles
- host: host donde arrancar el servidor WebSocket
- port: puerto donde escuchará el servidor
- path: ruta del endpoint WebSocket

## Ejemplo de conexión desde cliente

```javascript
const ws = new WebSocket('ws://localhost:8080/ws');
```

## Estructura recomendada en la aplicación

```
src/
  Task/
    StartServerTask.php
  Websocket/
    actions.php
    Modules/
      PingComponent.php
      LoginComponent.php
      SendMessageComponent.php
```

## Arranque del servidor

El servidor se arranca desde una tarea CLI definida por el usuario.

Ejemplo:

```php
<?php

declare(strict_types=1);

namespace Osumi\OsumiFramework\App\Task;

use Osumi\OsumiFramework\Core\OTask;
use Osumi\OsumiFramework\Plugins\OWebsocket\OWebsocket;

class StartServerTask extends OTask {
  public function run(array $options=[]): void {
    OWebsocket::setValidateMethod([$this, 'validateToken']);
    OWebsocket::loadActions();
    OWebsocket::start();
  }

  public function validateToken(string $token): ?array {
    if ($token === 'test-token') {
      return [
        'id' => 1,
        'name' => 'Admin'
      ];
    }

    return null;
  }
}
```

## Definición de acciones

Las acciones se definen en:

```
src/Websocket/actions.php
```

Ejemplo:

```php
<?php

declare(strict_types=1);

use Osumi\OsumiFramework\Plugins\OWebsocket\OWebsocketAction;
use Osumi\OsumiFramework\App\Websocket\Modules\PingComponent;
use Osumi\OsumiFramework\App\Websocket\Modules\LoginComponent;
use Osumi\OsumiFramework\App\Websocket\Modules\SendMessageComponent;

OWebsocketAction::register('ping', PingComponent::class);
OWebsocketAction::register('login', LoginComponent::class);
OWebsocketAction::register('send-message', SendMessageComponent::class, true);
```

## Formato de mensajes

Todos los mensajes recibidos deben tener esta estructura:

```json
{
  "action": "ping",
  "data": {}
}
```

Reglas
- action debe ser un string
- data debe ser siempre un objeto JSON
- data no puede ser un array indexado

Ejemplo válido:

```json
{
  "action": "send-message",
  "data": {
    "message": "Hola"
  }
}
```

Ejemplo no válido:

```json
{
  "action": "send-message",
  "data": ["Hola"]
}
```

## Respuestas

Los componentes deben devolver un string que contenga un JSON válido.

Ejemplo de respuesta correcta:

```json
{
  "status": "ok",
  "data": {
    "message": "pong"
  }
}
```

Ejemplo de error:

```json
{
  "status": "error",
  "error": "unknown_action"
}
```

## Componentes

Este plugin reutiliza los componentes normales de Osumi Framework.

Internamente, el plugin ejecuta:

```php
$component->render($request);
```

donde `$request` es un objeto `ORequest` construido a partir del campo data del mensaje recibido.

Esto permite usar en el componente métodos como:
- getParam()
- getParamString()
- getParamInt()
- getParamFloat()
- getParamBool()

Ejemplo de componente

```php
<?php

declare(strict_types=1);

namespace Osumi\OsumiFramework\App\Websocket\Modules;

use Osumi\OsumiFramework\Core\OComponent;
use Osumi\OsumiFramework\Web\ORequest;

class PingComponent extends OComponent {
  public string $status = 'ok';
  public array $data = [];

  public function run(ORequest $req): void {
    $this->data = [
      'message' => 'pong'
    ];
  }
}
```

Y su plantilla `PingTemplate.json`:

```json
{
  "status": "{{status}}",
  "data": {
    "message": "{{data.message}}"
  }
}
```

## Autenticación

El plugin no impone ninguna acción concreta para autenticar.

Es el usuario quien decide:
- cómo se llama la acción (login, authenticate, etc.)
- cómo obtiene el token
- cómo valida el token

## Validación de tokens

El servidor puede recibir un método validador mediante:

```php
OWebsocket::setValidateMethod(callable $validate_method);
```

La firma esperada es:

```php
function (string $token): ?array
```

- Si el token no es válido, debe devolver null
- Si es válido, debe devolver un array con al menos:
  - id

Ejemplo:

```php
public function validateToken(string $token): ?array {
  if ($token === 'test-token') {
    return [
      'id' => 1,
      'name' => 'Admin'
    ];
  }

  return null;
}
```

## Gestión de usuario

El plugin mantiene en memoria:
- conexiones abiertas
- usuarios asociados
- conexiones de cada usuario

Un usuario puede tener varias conexiones abiertas al mismo tiempo.

## Asociar usuario a la conexión actual

```php
OWebsocket::setUserData($id, $token, $data);
```

Ejemplo:

```php
OWebsocket::setUserData(
  1,
  'test-token',
  [
    'name' => 'Admin',
    'role' => 'admin'
  ]
);
```

## Obtener datos de usuario

Usuario actual:

```php
OWebsocket::getUserData();
```

Usuario concreto:

```php
OWebsocket::getUserData(1);
```

## Cerrar sesión o expulsar usuario

Usuario actual:

```php
OWebsocket::clearUserData();
```

Usuario concreto:

```php
OWebsocket::clearUserData(1);
```

Esto cerrará todas las conexiones asociadas a ese usuario y borrará sus datos de memoria.

## Envío de mensajes

Enviar a la conexión actual

```php
OWebsocket::send($json);
```

## Enviar a todas las conexiones de un usuario

```php
OWebsocket::sendToUser($id, $json);
```

## Broadcast global

```php
OWebsocket::broadcast($json);
```

## Broadcast a usuarios autenticados

```php
OWebsocket::broadcastAuthenticated($json);
```

## Depuración

El plugin ofrece un método para obtener información interna del estado actual:

```php
OWebsocket::getDebugInfo();
```

Devuelve un JSON con información sobre:
- conexión actual
- conexiones abiertas
- usuarios cargados en memoria
- acciones registradas

Ejemplo de uso desde un componente o acción de administración:

```php
$debug = OWebsocket::getDebugInfo();
```

## Errores estándar

Actualmente el plugin puede devolver estos errores:
- invalid_json
- bad_request
- unknown_action
- unauthorized
- invalid_response
- server_error

## Flujo general
1.	Se arranca el servidor desde una tarea CLI
2.	Se cargan las acciones definidas en `src/Websocket/actions.php`
3.	Un cliente abre una conexión WebSocket
4.	El cliente envía un mensaje con `action` y `data`
5.	El plugin valida el mensaje
6.	Busca la acción registrada
7.	Si la acción es protegida, valida la autenticación
8.	Crea un `ORequest` con los datos recibidos
9.	Ejecuta el componente asociado
10.	Envía la respuesta JSON al cliente

## Notas
- El plugin no modifica el core de Osumi Framework
- El plugin reutiliza el sistema de componentes existente
- El plugin está pensado para PHP 8.2 o superior
- La validación de autenticación queda totalmente en manos de la aplicación

## Licencia

MIT
