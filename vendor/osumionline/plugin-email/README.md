Osumi Framework Plugins: `OEmail`

Este plugin añade la clase `OEmail` al framework con la que se pueden enviar emails usando la función nativa `mail` de PHP. Por defecto se envían emails en formato HTML.

```php
$email = new OEmail();

// Remitente
$email->setFrom('user@example.com');
$email->setFromName('User name');
// Añadir destinatarios uno a uno
$email->addRecipient('user@gmail.com');
$email->addRecipient('user@hotmail.com');
// Añadir destinatarios mediante un array
$email->setRecipients(['user@gmail.com', 'user@hotmail.com']);
// Asunto
$email->setSubject('Asunto');
// Contenido del email (con HTML)
$email->setMessage('Contenido del email<br>con HTML');
// Contenido del email (texto plano)
$email->setIsHtml(false);
$email->setMessage('Contenido del email con texto plano');
// Adjuntos (uno a uno)
$email->addAttachment('/path/to/file.pdf');
// Adjuntos mediante un array
$email->setAttachments(['/path/to/file.pdf', '/path/to/another_file.pdf']);

// Enviar email
$email->send();

// Tras realizar el envío se puede comprobar la lista de usuarios a los que se les ha enviado y aquellos que han dado error
$usuarios_correctos = $email->getResultOk();
$usuarios_error = $email->getResultError();
```
