<?php declare(strict_types=1);

namespace OsumiFramework\OFW\DB;

class OModelField {
	private string | null $name        = null;
	private int | null    $type        = null;
	private bool          $has_default = true;
	private int | float | string | bool | null $default = null;
	private bool | null   $incr        = null;
	private int | null    $size        = null;
	private bool          $nullable    = true;
	private string | null $comment     = null;
	private string | null $ref         = null;

	function __construct(
		string $name = null,
		int $type = null,
		int | float | string | bool | null $default = '__OFW_DEFAULT__',
		bool | null $incr = null,
		int $size = null,
		bool $nullable = true,
		string $comment = null,
		string $ref = null
	) {
		$this->validate($name, $type, $default, $incr, $size, $nullable, $comment, $ref);
	}

	public function fromField(OModelField $field): void {
		$this->validate(
			$field->getName(),
			$field->getType(),
			$field->getDefault(),
			$field->getIncr(),
			$field->getSize(),
			$field->getNullable(),
			$field->getComment(),
			$field->getRef()
		);
		$this->has_default = $field->getHasDefault();
	}

	public function validate(
		string $name = null,
		int $type = null,
		int | float | string | bool | null $default = '__OFW_DEFAULT__',
		bool | null $incr = null,
		int $size = null,
		bool $nullable = true,
		string $comment = null,
		string $ref = null
	): void {
		// Field name is mandatory
		if (is_null($name)) {
			throw new \Exception('Name is mandatory for a field');
		}
		// Field type is mandatory
		if (is_null($type)) {
			throw new \Exception('Type is mandatory for a field');
		}
		// Primary keys are incremental by default
		if ($type === OMODEL_PK && is_null($incr)) {
			$incr = true;
			$nullable = false;
		}
		// If size is null field type defines the default size
		if (is_null($size)) {
			// Primary keys (numbers) and number are int fields, so length is 11
			if (
				$type === OMODEL_PK ||
				$type === OMODEL_NUM
			) {
				$size = 11;
			}
			// Varchar types are 50 characters long
			if (
				$type === OMODEL_PK_STR ||
				$type === OMODEL_TEXT
			) {
				$size = 50;
			}
			// Booleans are tinyints of 1 character long
			if ($type === OMODEL_BOOL) {
				$size = 1;
			}
		}
		// PK_STR and TEXT field length can not be more than 255
		if (($type === OMODEL_PK_STR || $type === OMODEL_TEXT) && !is_null($size) && $size > 255) {
			$size = 255;
		}
		// Created at fields cannot be nullable
		if ($type === OMODEL_CREATED) {
			$nullable = false;
		}
		// If the field is updated, it is nullable
		if ($type === OMODEL_UPDATED) {
			$default = null;
		}
		// If default is the preassigned code, there is no default value
		if ($default === '__OFW_DEFAULT__') {
			$this->has_default = false;
			$this->default = null;
		}
		else {
			$this->has_default = true;
			$this->default = $default;
		}
		if ($type === OMODEL_BOOL && !is_null($default) && is_bool($default)) {
			$default = ($default ? 1 : 0);
		}

		$this->name = $name;
		$this->type = $type;
		$this->incr = $incr;
		$this->size = $size;
		$this->nullable = $nullable;
		$this->comment = $comment;
		$this->ref = $ref;
	}

	public function getName(): string {
		return $this->name;
	}
	public function getType(): ?int {
		return $this->type;
	}
	public function getHasDefault(): bool {
		return $this->has_default;
	}
	public function getDefault(): int | float | string | bool | null {
		return $this->default;
	}
	public function getIncr(): bool | null {
		return $this->incr;
	}
	public function setIncr(bool $incr): void {
		$this->incr = $incr;
	}
	public function getSize(): ?int {
		return $this->size;
	}
	public function getNullable(): bool {
		return $this->nullable;
	}
	public function getComment(): ?string {
		return $this->comment;
	}
	public function getRef(): ?string {
		return $this->ref;
	}
}
