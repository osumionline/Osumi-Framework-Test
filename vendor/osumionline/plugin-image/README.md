Osumi Framework Plugins: `OImage`

Este plugin añade la clase `OImage` al framework con la que se puede manipular imágenes: cambiar de formato, redimensionar, escalar o rotar.

```php
$image = new OImage();

// Cargar una imagen
$image->load('/path/to/image.jpg'); // Permite archivos JPG, PNG, GIF y WEBP

// Guardar una imagen
// Permite indicar la ruta del nuevo archivo, el formato de imagen, el ratio de compresión y los permisos del nuevo archivo
// Por defecto el formato de imagen generado es JPG y el ratio de compresión es 75
$image->save('/path/to/new_image.avif', IMAGETYPE_AVIF, 100, 100);

// Obtener la extensión de la representación en Base64 de una imagen
$ext = $image->getImageExtension('data:image/png;base64,iVBORw0KGgoAAAANSUhEUg...'); // Devuelve "png"

// Obtener el tipo de la imagen cargada, devuelve una constante PHP
$type = $image->getImageType();

// Obtener la anchura de la imagen cargada (en pixels)
$width = $image->getWidth();

// Obtener la altura de la imagen cargada (en pixels)
$height = $image->getHeight();

// Escalar la imagen cargada a una altura fijada (la anchura se ajusta automaticamente)
$image->resizeToHeight(150);

// Escalar la imagen cargada a una anchura fijada (la altura se ajusta automaticamente)
$image->resizeToWidth(200);

// Escalar la imagen cargada a un porcentaje fijado
$image->scale(75);

// Escalar la imagen cargada a una anchura y altura fijadas
$image->resize(200, 150);

// Rotar la imagen cargada el número de grados indicado (no funciona con imágenes GIF)
$image->rotate(90);

// Recortar la imagen cargada a una proporción fija, manteniendo el centro
$image->cropToRatio('16:9');
$image->cropToRatio('9:16');
$image->cropToRatio('1:1');

// Recortar la imagen cargada a un tamaño exacto, manteniendo el centro
$image->cropToSize(1280, 760);
```
