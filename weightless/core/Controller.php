<?php

namespace Weightless\Core;

use Weightless\Core\Logic\Singleton;

abstract class Controller extends Singleton
{
  public static function getInstanceOf(string $className): Controller
  {
    return call_user_func("$className::getInstance");
  }

  public static function register(string $className): void
  {
    if (is_subclass_of($className, "Weightless\\Core\\Controller")) {
      $controller = call_user_func("$className::getInstance");
      $refl = new \ReflectionClass($controller);

      foreach ($refl->getMethods() as $method) {
        $attributes = $method->getAttributes();
        if (count($attributes) > 0) {
          $methodName = $method->getName();
          foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            $instance->targetClass = $controller;
            $instance->target = $methodName;
            $instance->execute();
          }
        }
      }
    }
  }
}
