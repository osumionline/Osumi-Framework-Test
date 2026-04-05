Osumi Framework Plugins: `OFile`

Este plugin añade la clase `OFile` al framework con la que se pueden manipular archivos mediante una serie de funciones estáticas.

```php
// Copiar un archivo
if (OFile::copy('/source/path', '/destinatio/path')) {
  echo "Archivo copiado.";
}

// Renombrar un archivo
if (OFile::rename('/path/old_name.txt', '/path/new_name.txt')) {
  echo "Archivo renombrado.";
}

// Borrar un archivo
if (OFile::delete('/path/file.txt')) {
  echo "Archivo borrado.";
}

// Borrar una carpeta, sus archivos y sub-carpetas recursivamente
if (Ofile::rrmdir('/path')) {
  echo "Carpeta borrada.";
}

// Crear un archivo comprimido con ZIP
OFile::zip('/path', '/destination_file.zip');
```
