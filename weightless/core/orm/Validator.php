<?php

namespace Weightless\Core\ORM;

class Validator
{
  /** @var array<string, mixed> */
  private array $errors = [];


  /** 
   * @param array<mixed> $data
   * @param array<mixed> $rules
   * */
  public function validate(array $data, array $rules): bool
  {
    foreach ($rules as $field => $ruleSet) {
      $rules = explode('|', $ruleSet);

      foreach ($rules as $rule) {
        if (method_exists($this, $rule)) {
          $this->{$rule}($field, $data[$field] ?? null);
        }
      }
    }

    return empty($this->errors);
  }

  public function required(string $field, mixed $value): void
  {
    if (empty($value) && $value !== '0') {
      $this->errors[$field][] = "The $field field is required.";
    }
  }

  public function string(string $field, mixed $value): void
  {
    if (!is_string($value)) {
      $this->errors[$field][] = "The $field field must be a string.";
    }
  }

  public function integer(string $field, mixed $value): void
  {
    if (!filter_var($value, FILTER_VALIDATE_INT)) {
      $this->errors[$field][] = "The $field field must be an integer.";
    }
  }

  /** @return array<mixed> */
  public function getErrors(): array
  {
    return $this->errors;
  }
}
