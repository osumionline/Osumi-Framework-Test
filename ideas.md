# Ideas

## EXTENSIONES NATURALES

### DTO con validadores reutilizables

```php
[ODTOField(validator: [MyValidators::class, 'iban'])]
public ?string $iban = null;
```

### Pipes custom definidos por el usuario

```php
OPipe::register('money', fn(float $v) => number_format($v, 2).' €');
```

## MEJORAS ESTRUCTURALES

### ORM

```php
Post::query()
  ->where('published', true)
  ->orderBy('created_at', 'desc')
  ->limit(20)
  ->fetchAll();
```

### Eventos explícitos del model

```php
protected function beforeSave(): void {}
protected function afterSave(): void {}
```

### Mejoras para OTask (CLI)

- Autocompletado zsh/bash.
- ofw make:component, make:dto, make:model, etc.

### Middlewares de respuesta

### Compilación estática de plantillas

## OTROS

### Inyección automática tipo Laravel/Symfony
