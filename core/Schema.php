<?php

namespace core;

class Schema
{
  protected $columns = [];
  protected $modifications = [];

  public function id()
  {
    $this->columns[] = 'id INT AUTO_INCREMENT PRIMARY KEY';
    return $this;
  }

  public function string($name, $length = 255)
  {
    $this->columns[] = "$name VARCHAR($length) NOT NULL";
    $this->modifications[] = "ADD `$name` VARCHAR($length) NOT NULL";
    return $this;
  }

  public function text($name)
  {
    $this->columns[] = "$name TEXT NOT NULL";
    $this->modifications[] = "ADD `$name` TEXT NOT NULL";
    return $this;
  }

  public function enum($name, array $values)
  {
    $enumValues = implode("','", $values);
    $this->columns[] = "$name ENUM('$enumValues') NOT NULL";
    $this->modifications[] = "ADD `$name` ENUM('$enumValues') NOT NULL";
    return $this;
  }

  public function integer($name)
  {
    $this->columns[] = "$name INT NOT NULL";
    $this->modifications[] = "ADD `$name` INT NOT NULL";
    return $this;
  }

  public function boolean($name)
  {
    $this->columns[] = "$name BOOLEAN NOT NULL";
    $this->modifications[] = "ADD `$name` BOOLEAN NOT NULL";
    return $this;
  }

  public function timestamp($name)
  {
    $this->columns[] = "$name TIMESTAMP NOT NULL";
    $this->modifications[] = "ADD `$name` TIMESTAMP NOT NULL";
    return $this;
  }

  public function timestamps()
  {
    $this->timestamp('created_at')->default('NOW');
    $this->timestamp('updated_at')->nullable();
    return $this;
  }

  public function nullable()
  {
    if (!empty($this->columns)) {
      $this->columns[count($this->columns) - 1] = str_replace('NOT NULL', 'NULL', $this->columns[count($this->columns) - 1]);
      $this->modifications[count($this->modifications) - 1] = str_replace('NOT NULL', 'NULL', $this->modifications[count($this->modifications) - 1]);
    }
    return $this;
  }

  public function unique()
  {
    if (!empty($this->columns)) {
      $lastColumn = array_pop($this->columns);
      $this->columns[] = "$lastColumn UNIQUE";
    }
    return $this;
  }

  public function default($value)
  {
    if (!empty($this->columns)) {
      $this->columns[count($this->columns) - 1] .= " DEFAULT '$value'";
      $this->modifications[count($this->modifications) - 1] .= " DEFAULT '$value'";
    }
    return $this;
  }

  public function after($column)
  {
    if (!empty($this->columns)) {
      $this->modifications[count($this->modifications) - 1] .= " AFTER `$column`";
    }
    return $this;
  }

  public function getColumns()
  {
    return $this->columns;
  }

  public function getModifications()
  {
    return $this->modifications;
  }
}
