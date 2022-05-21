<?php
class Prueba {
	public function addVar(string $name): void {
		$this->{$name} = 'hola';
	}
}

$pr = new Prueba();
$pr->addVar('variable');

echo $pr->variable;

// https://stackoverflow.com/a/8707277/921329