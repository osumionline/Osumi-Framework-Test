<?php declare(strict_types=1);

namespace OsumiFramework\OFW\DB;

class OModelFieldDate extends OModelField {
  private bool $original_set = false;
  private string | null $original_value = null;
  private string | null $current_value = null;
  private string | null $extra = null;
  public const SET_EXTRA = true;
  public const GET_EXTRA = true;

  function __construct(OModelField $field) {
    parent::fromField($field);
  }

  public function set(mixed $value, string | null $extra = null): void {
    if (is_string($value) || is_null($value)) {
      if ($this->original_set) {
        $this->current_value = $value;
      }
      else {
        $this->original_set = true;
        $this->original_value = $value;
        $this->current_value = $value;
      }
      if (!is_null($extra)) {
        $this->extra = $extra;
      }
    }
    else {
      throw new \Exception('Value "'.strval($value).'" must be a string or null.');
    }
  }

  public function get(string | null $extra = null): string | null {
    if (!is_null($this->current_value) && !is_null($extra)) {
      return date($extra, str_to_time($this->current_value));
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
    if (!is_null($this->extra)) {
      return 'DATE_FORMAT(`'.$this->getName().'`, "'.$this->extra.'") = ?';
    }
    return "`".$this->getName()."` = ?";
  }

  public function generate(): string {
    $sql = "  `".$this->getName()."` DATETIME";

    if (!$this->getNullable() || !is_null($this->getRef())) {
      $sql .= " NOT NULL";
    }
    if ($this->getHasDefault()) {
      $sql .= " DEFAULT ".(is_null($this->getDefault()) ? "NULL" : "'".$this->getDefault()."'");
    }
    if (!is_null($this->getComment())) {
      $sql .= " COMMENT '".$this->getComment()."'";
    }

    return $sql;
  }
}
