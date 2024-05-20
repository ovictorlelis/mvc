<?php

namespace core;

use PDO;

class DB
{
    private static $instance = null;

    public static function getInstance()
    {
        if (!self::$instance) {
            $pdo = new PDO(env('DB_CONNECTION') . ':host=' . env('DB_HOST') . ';dbname=' . env('DB_DATABASE'), env('DB_USERNAME'), env('DB_PASSWORD'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            self::$instance = new Database($pdo);
        }

        return self::$instance;
    }

    public static function __callStatic($method, $args)
    {
        $instance = self::getInstance();
        return call_user_func_array([$instance, $method], $args);
    }
}
