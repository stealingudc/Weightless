<?php

namespace Weightless\Core\ORM;

use Weightless\Core\Environment;

class Database
{
  // Cannot test unimplemented method
  // @codeCoverageIgnoreStart
  protected function __construct() {}
  // @codeCoverageIgnoreEnd
  private static ?\PDO $pdo = null;

  final public static function getConnection(): \PDO
  {
    if (self::$pdo === null) {
      $dotenv = Environment::getInstance()->variables;
      $db_host = $dotenv["MYSQL_HOST"];
      $db_name = $dotenv["MYSQL_DATABASE"];
      $db_user = $dotenv["MYSQL_USER"];
      $db_password = $dotenv["MYSQL_PASSWORD"];

      $dsn = "mysql:host=$db_host;dbname=$db_name";
      self::$pdo = new \PDO($dsn, $db_user, $db_password);
      self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
    return self::$pdo;
  }

  // Cannot test magic method
  // @codeCoverageIgnoreStart
  private function __clone() {}
  // @codeCoverageIgnoreEnd
}
