<?php

namespace Weightless\Core\Attribute;

use Weightless\Core\Router;

#[\Attribute]
class Route extends Attribute
{
  /**
   * @param array<string> $methods
   * */
  public function __construct(public string $url, public array $methods) {}

  #[\Override]
  public function execute(): void
  {
    $obj = $this;
    $closure = function ($args = null) use ($obj): void {
      if (!class_exists($obj->targetClass)) {
        throw new \ReflectionException("Class {$obj->targetClass} does not exist");
      }
      $refl = new \ReflectionClass($obj->targetClass);
      $instance = $refl->getMethod("getInstance")->invoke(null);
      $callback = [$instance, $obj->target];
      if (!is_callable($callback)) {
        throw new \BadFunctionCallException("Function {$obj->target} does not exist on class {$obj->targetClass}");
      }
      if ($args === null) {
        call_user_func($callback);
        return;
      }
      if (!is_array($args)) {
        call_user_func_array($callback, [$args]);
        return;
      }
      call_user_func_array($callback, [$args]);
    };
    Router::getInstance()->match($this->methods, $this->url, $closure);
  }
}
