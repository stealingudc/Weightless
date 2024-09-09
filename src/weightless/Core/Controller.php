<?php

namespace Weightless\Core;

use RuntimeException;
use Weightless\Core\Attribute\Attribute;
use Weightless\Core\Exception\InvalidClassNameException;
use Weightless\Core\Logic\Singleton;

abstract class Controller extends Singleton
{
  public static function getInstanceOf(string $className): ?Controller
  {
    if (!class_exists($className)) {
      throw new InvalidClassNameException($className);
    }
    $refl = new \ReflectionClass($className);
    $instance = $refl->getMethod("getInstance")->invoke(null);
    if ($instance instanceof Controller) {
      return $instance;
    }
    return null;
  }

  public static function register(string $className): void
  {
    if (is_subclass_of($className, \Weightless\Core\Controller::class)) {
      $refl = new \ReflectionClass($className);

      foreach ($refl->getMethods() as $method) {
        $attributes = $method->getAttributes();
        if (count($attributes) > 0) {
          $methodName = $method->getName();
          foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if (!$instance instanceof Attribute) {
              throw new RuntimeException("What even happened here?");
            }
            $instance->targetClass = $className;
            $instance->target = $methodName;
            $instance->execute();
          }
        }
      }
    }
  }
}
