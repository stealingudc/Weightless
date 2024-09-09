<?php

namespace Weightless\Core;

final class Environment
{
 /**
  * @var array<string, string> $variables
  */
  public array $variables = [];
  protected function __construct()
  {
    self::parseFile(".env");
  }

  final public static function getInstance(): static
  {
    static $instances = [];
    if (empty($instances[self::class])) {
      $instances[self::class] = new static();
    }
    return $instances[self::class];
  }

  private function parseFile(string $path): void
  {
    if (!file_exists($path)) {
      throw new \InvalidArgumentException(sprintf('%s file does not exist', $path));
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if($lines === false){
      trigger_error("The specified .env file does not exist");
      return;
    }
    foreach ($lines as $line) {
      // Skip comments
      if (str_starts_with(trim($line), '#')) {
        continue;
      }

      // Split by the first equals sign
      [$name, $value] = explode('=', $line, 2);

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
