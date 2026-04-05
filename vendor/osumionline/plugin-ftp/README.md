Osumi Framework Plugins: `OFTP`

Este plugin añade la clase `OFTP` al framework con la que se puede conectar a servidores remotos mediante FTP y realizar las acciones más típicas (moverse por el servidor, subir o bajar archivos, borrarlos...).

```php
$ftp = new OFTP('host', 'user', 'password');

// Abrir conexión
if ($ftp->connect()) {
  echo "Conexión abierta al servidor remoto.";
}

// Iniciar sesión
if ($ftp->login()) {
  echo "Sesión iniciada."
}

// Establecer conexión pasiva
$ftp->passive(true);

// Desconectar la conexión automaticamente tras realizar un comando
$ftp->autoDisconnect(true);

// Establecer modo de conexión ('ascii' / 'bin')
$ftp->mode('bin');

// Comprobar si la conexión sigue abierta y reconectar en caso contrario
$ftp->checkConnection();

// Subir un archivo al servidor remoto
if ($ftp->put('/local/path/to/file.txt', '/remote/path/to/file.txt')) {
  echo "Archivo subido correctamente.";
}

// Descargar un archivo del servidor remoto
if ($ftp->get('/remote/path/to/file.txt', '/local/path/to/file.txt')) {
  echo "Archivo descargado correctamente.";
}

// Borrar un archivo del servidor remoto
if ($ftp->delete('/remote/path/to/file.txt')) {
  echo "Archivo borrado correctamente.";
}

// Cambiar de ruta en el servidor remoto
if ($ftp->chdir('/remote/path/')) {
  echo "Ruta cambiada correctamente.";
}

// Crear una carpeta en el servidor remoto
if ($ftp->mkdir('/remote/path/new/')) {
  echo "Carpeta creada correctamente.";
}

// Cerrar conexión al servidor remoto.
$ftp->disconnect();
```
