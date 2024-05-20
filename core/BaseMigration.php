<?php

namespace core;

use PDO;
use PDOException;

class BaseMigration
{
  protected $pdo;

  public function __construct()
  {
    Environment::load(dirname(__FILE__, 2));

    try {
      $this->pdo = new PDO(getenv('DB_CONNECTION') . ':host=' . getenv('DB_HOST'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ]);

      $this->pdo->exec("USE " . getenv('DB_DATABASE'));
    } catch (PDOException $e) {
      throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
  }

  public function createDatabase($database)
  {
    if (!empty($database)) {
      $sql = "CREATE DATABASE IF NOT EXISTS {$database}";
      $this->pdo->exec($sql);
    } else {
      echo "Database name is empty. Please provide a valid database name." . PHP_EOL;
    }
  }

  public function connectToDatabase()
  {
    try {
      $this->pdo = new PDO(getenv('DB_CONNECTION') . ':host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ]);
    } catch (PDOException $e) {
      throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
  }

  protected function createTable($table, \Closure $callback)
  {
    $schema = new Schema();
    $callback($schema);
    $columns = $schema->getColumns();
    $columnsSql = implode(",\n", $columns);
    $sql = "CREATE TABLE {$table} (\n{$columnsSql}\n) ENGINE=INNODB;";
    $this->pdo->exec($sql);
  }

  protected function dropTable($table)
  {
    $sql = "DROP TABLE IF EXISTS {$table};";
    $this->pdo->exec($sql);
  }

  protected function alterTable($table, \Closure $callback)
  {
    $schema = new Schema();
    $callback($schema);
    $modifications = $schema->getModifications();

    if (!empty($modifications)) {
      $sql = "ALTER TABLE {$table} " . implode(", ", $modifications) . ";";
      echo $sql . PHP_EOL; // Debugging line
      $this->pdo->exec($sql);
    }
  }
}
