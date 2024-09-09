<?php

namespace Weightless\Core\Attribute;

use ReflectionClass;
use Weightless\Core\Router as Router;

#[\Attribute]
class Attribute
{
  public string $targetClass;
  public string $target;
  public mixed $args;

  public function __construct(public string $attributeName, mixed ...$args)
  {
    $this->args = $args;
  }

  public function execute(): void
  {
    // TODO: clean this up, lol
    $routeArgs = [];
    if (!class_exists($this->targetClass)) {
      throw new \ReflectionException("Class {$this->targetClass} does not exist");
    }
    $targetClassRefl = new ReflectionClass($this->targetClass);
    $method = $targetClassRefl->getMethod($this->target);
    $attrs = $method->getAttributes();
    foreach ($attrs as $attr) {
      if ($attr->getName() === \Weightless\Core\Attribute\Route::class) {
        $routeArgs = $attr->getArguments();
        break;
      }
    }

    if (count($routeArgs) > 0) {
      $obj = $this;
      $closure = function () use ($obj): void {
        $callback = [$obj->targetClass, $obj->target];
        if (!is_callable($callback)) {
          throw new \BadFunctionCallException("Function {$obj->target} does not exist on class {$obj->targetClass}");
        }
        if (!class_exists($this->attributeName)) {
          throw new \ReflectionException("Class {$this->attributeName} does not exist");
        }
        call_user_func($callback);
        $refl = new ReflectionClass($this->attributeName);
        $instance = $refl->newInstance($this->args);
        if ($instance instanceof self) {
          $instance->execute();
        }
      };

      echo "<script src='https://unpkg.com/htmx.org@2.0.2'></script>";
      Router::getInstance()->match($routeArgs["methods"] ?? $routeArgs[1], $routeArgs[0], $closure);
      // Router::routeMethods($routeArgs[0], $closure, $routeArgs["methods"] ?? $routeArgs[1]);
    }
  }
}
