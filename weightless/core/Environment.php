<?php

namespace Weightless\Core;

class Environment
{
  public array $variables = [];
  protected function __construct()
  {
    self::parseFile(".env");
  }

  final public static function getInstance(): static
  {
    static $instances = [];
    if (empty($instances[static::class])) {
      $instances[static::class] = new static();
    }
    return $instances[static::class];
  }

  private function parseFile(string $path)
  {
    if (!file_exists($path)) {
      throw new \InvalidArgumentException(sprintf('%s file does not exist', $path));
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
      // Skip comments
      if (strpos(trim($line), '#') === 0) {
        continue;
      }

      // Split by the first equals sign
      list($name, $value) = explode('=', $line, 2);

      // Remove surrounding quotes (if any)
      $name = trim($name);
      $value = trim($value);

      if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
      }
      $this->variables[$name] = $value;
    }
  }

  private function __clone() {}
}
