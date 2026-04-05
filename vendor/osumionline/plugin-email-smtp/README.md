Osumi Framework Plugins: `OEmailSMTP`

Este plugin añade la clase `OEmailSMTP` al framework con la que se pueden enviar emails usando la librería `PHPMailer`. Usando esta librería se pueden realizar envíos mediante SMTP con servicios como, por ejemplo, GMail. La consiguración se realiza en el archivo `Config.json` general de la aplicación.

Configuración

```json
{
  ...,
  "plugins": {
      "email_smtp": {
          "host": "smtp.gmail.com",
          "port": 587,
          "secure": "tls",
          "user": "user@gmail.com",
          "pass": "password"
      }
  },
}
```

Uso del plugin

```php
$email = new OEmailSMTP();

// Remitente
$email->setFromName('User name'); // La dirección del remitente se configura en Config.json
// Añadir destinatarios uno a uno
$email->addRecipient('user@gmail.com');
$email->addRecipient('user@hotmail.com');
// Añadir destinatarios mediante un array
$email->setRecipients(['user@gmail.com', 'user@hotmail.com']);
// Añadir destinatario en copia
$email->setCC('another_user@gmail.com');
// Añadir destinatario en copia oculta
$email->setBCC('hidden@gmail.com');
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
