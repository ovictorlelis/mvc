<?php

namespace core;

class Validator
{
  protected $data = [];
  protected $errors = [];

  public function validate($data, $rules)
  {
    $this->data = $data;
    $this->errors = [];

    foreach ($rules as $field => $rule) {
      $this->applyRules($field, $rule);
    }

    if (!empty($this->errors)) {
      session()->set('errors', $this->errors);
    }

    return empty($this->errors);
  }

  protected function applyRules($field, $rules)
  {
    $rules = explode('|', $rules);

    foreach ($rules as $rule) {
      $ruleName = $rule;
      $ruleParams = [];

      if (strpos($rule, ':') !== false) {
        list($ruleName, $paramStr) = explode(':', $rule, 2);
        $ruleParams = explode(',', $paramStr);
      }

      $methodName = 'validate' . ucfirst($ruleName);

      if (method_exists($this, $methodName)) {
        $this->$methodName($field, ...$ruleParams);
      }
    }
  }

  protected function validateRequired($field)
  {
    if (empty($this->data[$field])) {
      $this->addError($field, 'O campo é obrigatório.');
    }
  }

  protected function validateEmail($field)
  {
    if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
      $this->addError($field, 'O campo deve ser um endereço de e-mail válido.');
    }
  }

  protected function validateMin($field, $min)
  {
    if (strlen($this->data[$field]) < $min) {
      $this->addError($field, "O campo deve ter pelo menos $min caracteres.");
    }
  }

  protected function validateMax($field, $max)
  {
    if (strlen($this->data[$field]) > $max) {
      $this->addError($field, "O campo deve ter no máximo $max caracteres.");
    }
  }

  protected function validateBetween($field, $min, $max)
  {
    $length = strlen($this->data[$field]);
    if ($length < $min || $length > $max) {
      $this->addError($field, "O campo deve ter entre $min e $max caracteres.");
    }
  }

  protected function validateNumeric($field)
  {
    if (!is_numeric($this->data[$field])) {
      $this->addError($field, 'O campo deve ser um número.');
    }
  }

  protected function validateInteger($field)
  {
    if (!filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
      $this->addError($field, 'O campo deve ser um número inteiro.');
    }
  }

  protected function validateBoolean($field)
  {
    if (filter_var($this->data[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
      $this->addError($field, 'O campo deve ser verdadeiro ou falso.');
    }
  }

  protected function validateDate($field)
  {
    if (!strtotime($this->data[$field])) {
      $this->addError($field, 'O campo deve ser uma data válida.');
    }
  }

  protected function validateBefore($field, $date)
  {
    if (strtotime($this->data[$field]) >= strtotime($date)) {
      $this->addError($field, "O campo deve ser uma data antes de $date.");
    }
  }

  protected function validateAfter($field, $date)
  {
    if (strtotime($this->data[$field]) <= strtotime($date)) {
      $this->addError($field, "O campo deve ser uma data depois de $date.");
    }
  }

  protected function validateSame($field, $otherField)
  {
    if ($this->data[$field] !== $this->data[$otherField]) {
      $this->addError($field, "O campo deve ser igual ao campo $otherField.");
    }
  }

  protected function validateDifferent($field, $otherField)
  {
    if ($this->data[$field] === $this->data[$otherField]) {
      $this->addError($field, "O campo deve ser diferente do campo $otherField.");
    }
  }

  protected function validateIn($field, ...$values)
  {
    if (!in_array($this->data[$field], $values)) {
      $this->addError($field, 'O campo deve ser um dos seguintes valores: ' . implode(', ', $values) . '.');
    }
  }

  protected function validateRegex($field, $pattern)
  {
    if (!preg_match($pattern, $this->data[$field])) {
      $this->addError($field, 'O campo tem um formato inválido.');
    }
  }

  protected function validateUnique($field, $table, $column = null)
  {
    $column = $column ?: $field;
    $result = DB::query("SELECT COUNT(*) as count FROM $table WHERE $column = ?", [$this->data[$field]]);

    if ($result && isset($result->count) && $result->count > 0) {
      $this->addError($field, 'O campo deve ser único.');
    }
  }

  protected function addError($field, $message)
  {
    $this->errors[$field][] = $message;
  }

  public function errors()
  {
    return $this->errors;
  }
}
