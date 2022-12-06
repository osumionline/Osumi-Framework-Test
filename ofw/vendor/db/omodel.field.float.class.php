<?php declare(strict_types=1);

namespace OsumiFramework\OFW\DB;

class OModelFieldFloat extends OModelField {
  private bool $original_set = false;
  private float | null $original_value = null;
  private float | null $current_value = null;
  public const SET_EXTRA = true;
  public const GET_EXTRA = true;

  function __construct(OModelField $field) {
    parent::fromField($field);
  }

  public function set(mixed $value, int | null $extra = null): void {
    if (is_float($value) || is_null($value)) {
      if (!is_null($value) && !is_null($extra)) {
        $value = floatval(number_format($value, $extra));
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
      throw new \Exception('Value "'.strval($value).'" must be a float or null.');
    }
  }

  public function get(int | null $extra = null): float | null {
    if (!is_null($this->current_value) && !is_null($extra)) {
      return floatval(number_format($value, $extra));
    }
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
    $sql = "  `".$this->getName()."` FLOAT";

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
