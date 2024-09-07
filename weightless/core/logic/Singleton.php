<?php

namespace Weightless\Core\Logic;

abstract class Singleton
{
  protected function __construct() {}
  final public static function getInstance(): static
  {
    static $instances = [];
    if (empty($instances[static::class])) {
      $instances[static::class] = new static();
    }
    return $instances[static::class];
  }

  private function __clone() {}
}
