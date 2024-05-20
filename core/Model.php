<?php

namespace core;

use PDO;

class Model extends Database
{
    private static $instance = null;
    protected $table;

    public function __construct()
    {
        if (!self::$instance) {
            $this->pdo = new PDO(env('DB_CONNECTION') . ':host=' . env('DB_HOST') . ';dbname=' . env('DB_DATABASE'), env('DB_USERNAME'), env('DB_PASSWORD'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }

        if (empty($this->table)) {
            $this->table = $this->getTableName();
        }

        return self::$instance;
    }

    public static function getTableName()
    {
        $className = explode('\\', get_called_class());
        $className = end($className);
        return strtolower($className) . 's';
    }
}
