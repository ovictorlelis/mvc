<?php

namespace core;

class Environment
{
  public static function load($dir)
  {
    $path = $dir . '/.env';
    if (!file_exists($path)) {
      return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
      if (preg_match('/^\s*#|^\s*;|^\s*$/', $line)) {
        continue;
      }

      list($name, $value) = explode('=', $line, 2);
      $name = trim($name);
      $value = trim($value);

      if (preg_match('/^"(.*)"$/', $value, $matches)) {
        $value = $matches[1];
      } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
        $value = $matches[1];
      }

      self::setEnvVariable($name, $value);
    }

    return true;
  }

  protected static function setEnvVariable($name, $value)
  {
    putenv("$name=$value");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
  }
}
