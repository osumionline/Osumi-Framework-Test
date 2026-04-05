Osumi Framework Plugins: `OCrypt`

Este plugin añade la clase `OCrypt` al framework con el cifrar / descifrar textos usando OpenSSL:

```php
$crypt = new OCrypt('secret_key');
$encrypted_text = $crypt->encrypt('text'); // Resultado: "n0HrXZ6rj8CdxYB0xqGt4FTzsh0="
$decrypted_text = $crypt->decrypt('n0HrXZ6rj8CdxYB0xqGt4FTzsh0='); // Resultado: "text"
```
