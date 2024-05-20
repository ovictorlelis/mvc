<?php

namespace core;

use PDO;
use PDOException;

class Migration
{
  protected $pdo;

  public function __construct()
  {
    Environment::load(dirname(__FILE__, 2));
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

  public function createMigrationsTable()
  {
    $this->pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )  ENGINE=INNODB;");
  }

  public function getAppliedMigrations()
  {
    $this->createMigrationsTable();
    $statement = $this->pdo->query("SELECT migration FROM migrations");
    return $statement->fetchAll(PDO::FETCH_COLUMN);
  }

  public function saveMigration($migration)
  {
    $statement = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
    $statement->execute([$migration]);
  }

  public function applyMigrations()
  {
    $this->createMigrationsTable();
    $appliedMigrations = $this->getAppliedMigrations();

    $files = array_diff(scandir(__DIR__ . '/../migrations'), ['.', '..']);
    $toApplyMigrations = array_diff($files, $appliedMigrations);

    foreach ($toApplyMigrations as $migration) {
      require_once __DIR__ . '/../migrations/' . $migration;

      $className = preg_replace("/(.*?)_(.*)/", "$2", $migration);
      $className = str_replace(".php", "", $className);
      $className = ucfirst($className);

      $instance = new $className();
      $instance->up();
      $this->saveMigration($migration);
      echo "Applied migration: $migration" . PHP_EOL;
    }

    if (empty($toApplyMigrations)) {
      echo "All migrations are applied." . PHP_EOL;
    }
  }

  public function refresh()
  {
    $this->dropAllTables();
    $this->applyMigrations();
  }

  protected function dropAllTables()
  {
    $tables = $this->pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
      $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`;");
    }
  }

  protected function getMigrations()
  {
    return array_diff(scandir(__DIR__ . '/../migrations'), ['.', '..']);
  }
}
