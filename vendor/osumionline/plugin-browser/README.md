Osumi Framework Plugins: `OBrowser`

Este plugin añade la clase `OBrowser` al framework con el que obtener información del navegador del usuario basándose en su User Agent:

```php
$browser = new OBrowser();
$browser->setUA( $_SERVER['HTTP_USER_AGENT'] );

if ($browser->isIpad()) {
  echo "Dispositivo iPad detectado.";
}
if ($browser->isMobile()) {
  echo "Navegador movil detectado.";
}
```
