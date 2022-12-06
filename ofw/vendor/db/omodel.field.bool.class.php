<?php declare(strict_types=1);

namespace OsumiFramework\OFW\DB;

class OModelFieldBool extends OModelField {
  private bool $original_set = false;
  private bool | null $original_value = null;
  private bool | null $current_value = null;
  public const SET_EXTRA = false;
  public const GET_EXTRA = false;

  function __construct(OModelField $field) {
    parent::fromField($field);
  }

  public function set(mixed $value): void {
    if (is_int($value) || is_bool($value) || is_null($value)) {
      if (is_int($value)) {
        $value = boolval($value);
      }
      if ($this->original_set) {
        $this->current_value = $value;
      }
      else {
        $this->original_set = true;
        $this->original_value = $value;
        $this->current_value = $value;
      }
    }
    else {
      throw new \Exception('Value "'.strval($value).'" must be an integer, a boolean or null.');
    }
  }

  public function get(): bool | null {
    return $this->current_value;
  }

  public function changed(): bool {
    return ($this->original_set && $this->original_value !== $this->current_value);
  }

  public function reset(): void {
  	$this->original_value = $this->current_value;
  }

  public function getUpdateStr(): string {
    return "`".$this->getName()."` = ?";
  }

  public function generate(): string {
    $sql = "  `".$this->getName()."` TINYINT(1)";

    if (!$this->getNullable() || !is_null($this->getRef())) {
      $sql .= " NOT NULL";
    }
    if ($this->getHasDefault()) {
      $sql .= " DEFAULT ".(is_null($this->getDefault()) ? "NULL" : $this->getDefault());
    }
    if (!is_null($this->getComment())) {
      $sql .= " COMMENT '".$this->getComment()."'";
    }

    return $sql;
  }
}
