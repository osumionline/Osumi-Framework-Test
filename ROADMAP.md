ROADMAP
=======

## 8.0.2

* `depends` en componentes, cambiarlo de string a array. Así se evitará que parsearlo en cada solicitud.

##  8.0.3

* `actions` en módulos, cambiarlo de string a array. Así se evitará que parsearlo en cada solicitud.

## 8.0.4

* Cambiar `$req->getFilter('login')`, quitar parámetro. Si solo hay un filtro… no hace falta indicar cuál. O implementar múltiples filtros. Por decidir.

## 8.0.5

* Cambiar DTOs a nombre.dto.php

## 8.1

* Investigar `eagerLoader`: recorrer archivos que se van a cargar, analizar líneas de "use ..." e ir incluyendo y analizando recursivamente. Esto haría obsoletos todos los parametros de servicios, componentes, los `loadService` y `loadComponent`... ya que los archivos necesarios se cargarían mirando "lo que se va a usar".
* Opción `static` en `OModuleAction` para crear URLs estáticas, que ignoren la acción y ni siquiera pasen por `OTemplate`.
* Comprobación carpetas del framework, si no existe `app` se crea, si no existe `config` se crea...
* Permitir que no haya `config.json`

EagerLoader

OCore

run {
	…
	$this->eagerLoader("/ruta/archivo.action.php");
	…
}

eagerLoader ($ruta) {
	$contenido = file_get_contents($ruta)
	$dto = $this->getContentDTO($contenido) // nombre en camel case
	if (!is_null($dto)) {
		require_once $dto;
	}

	$components =  $this->getContentComponents($contenido)
	// [ "ruta/componente", "componente" ]
	foreach ($components as $component) {
		$this->loadComponent($component)
	}
}

getContentDTO($contenido) {
	/**
	 * Busca "use OsumiFramework\App\DTO\XXXXX";
	 * Si encuentra, devuelve snake_case de resultado, sino null
	 */
}

getContentComponents($contenido) {
	/**
	 * Busca "use OsumiFramework\App\Component\XXXXX";
	 * Resultado puede ser carpeta/componente o solo componente
	 * Explotar por barras, hacer snake_case de cada parte y devolver otra vez pegadas por barras:
	 * Model/PhotoList -> model/photo_list
	 * Devuelve array de resultados o array vacío si no hay
	 */
}

loadComponent($ruta) {
	$ruta_component = $ruta.".component.php";
	$ruta_template = $ruta.".template.php";
	require_once $ruta_component;

	$subcomponents = [];
	$contenido_component = file_get_contents($ruta_component);
	array_merge($subcomponents, $this->getContentComponents($contenido_component));
	$contenido_template = file_get_contents($ruta_template);
	array_merge($subcomponents, $this->getContentComponents($contenido_template));

	foreach ($subcomponents as $sub) {
		$this->loadComponent($sub);
	}
}
