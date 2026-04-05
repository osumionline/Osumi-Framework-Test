Osumi Framework Plugins: `OToken`

Este plugin añade la clase `OToken` al framework con la que se pueden crear y gestionar tokens de tipo JWT. Para usarse hay que pasar una clave con la que firmar el token en el constructor:

```php
$tk = new OToken("1234bf577a76645dbabcdbc379998243ac1c1234");
$tk->addParam('id', $id);
$tk->addParam('name', $name);
$tk->addParam('email', $email);
$tk->addParam('exp', time() + (24 * 60 * 60));

$token = $tk->getToken();
```

Posteriormente para comprobar la validez de un token y obtener sus datos, se debe inicializar usando la misma clave con la que se creó y luego usar el método `checkToken` al que se le pasaría el contenido del token JWT a comprobar:

```php
$tk = new OToken("1234bf577a76645dbabcdbc379998243ac1c1234");
if ($tk->checkToken($headers['Authorization'])) {
  $id = $tk->getParam('id');
  $name = $tk->getParam('name');
  $email = $tk->getParam('email');
}
else {
  echo "ERROR: El token no es válido."
}
```
