<?php

namespace Weightless\Core\Attribute;

use ReflectionClass;
use Weightless\Core\Router as Router;

interface IAttribute
{
  public function onFunctionCall(): void;
}

#[\Attribute]
class Attribute implements IAttribute
{
  public string $targetClass;
  public string $target;
  public mixed $args;

  public function __construct(public string $attributeName, mixed ...$args)
  {
    $this->args = $args;
  }

  public function onFunctionCall(): void {}

  final public function execute(): void
  {
    // TODO: clean this up, lol
    $routeArgs = [];
    // @phpstan-ignore-next-line (If class doesn't exist, Error is thrown first)
    $targetClassRefl = new ReflectionClass($this->targetClass);
    $method = $targetClassRefl->getMethod($this->target);
    $attrs = $method->getAttributes();
    foreach ($attrs as $attr) {
      if ($attr->getName() === \Weightless\Core\Attribute\Route::class) {
        // Cannot be tested
        // @codeCoverageIgnoreStart
        $routeArgs = $attr->getArguments();
        // @codeCoverageIgnoreEnd
      }
    }

    $obj = $this;
    $closure = function () use ($obj): void {
      $callback = [$obj->targetClass, $obj->target];
      if (!class_exists($obj->attributeName)) {
        throw new \ReflectionException("Class {$obj->attributeName} does not exist");
      }
      if ((new \ReflectionMethod($obj->targetClass, $obj->target))->isStatic()) {
        // @phpstan-ignore-next-line (Will throw "ReflectionException - method does not exist" first)
        call_user_func($callback);
      }
      $refl = new ReflectionClass($obj->attributeName);
      $instance = $refl->newInstance($obj->attributeName, $obj->args);
      if ($instance instanceof self) {
        $instance->targetClass = $obj->targetClass;
        $instance->target = $obj->target;
        $instance->onFunctionCall();
        // $instance->execute();
      }
    };
    if (count($routeArgs) > 0) {
      // echo "<script src='https://unpkg.com/htmx.org@2.0.2'></script>";
      // Cannot be tested
      // @codeCoverageIgnoreStart
      Router::getInstance()->match($routeArgs["methods"] ?? $routeArgs[1], $routeArgs[0], $closure);
      // @codeCoverageIgnoreEnd
      // Router::routeMethods($routeArgs[0], $closure, $routeArgs["methods"] ?? $routeArgs[1]);
    }
    $closure();
  }
}
