<?php

namespace Weightless\Core\Attribute;

use Weightless\Core\Router;

#[\Attribute]
class Route extends Attribute
{
  /**
   * @param array<string> $methods
   * */
  public function __construct(public string $url, public array $methods)
  {
    parent::__construct(attributeName: self::class);
  }

  #[\Override]
  public function onFunctionCall(): void
  {
    if (!class_exists($this->targetClass)) {
      throw new \ReflectionException("Class {$this->targetClass} does not exist");
    }
    $refl = new \ReflectionClass($this->targetClass);
    $instance = $refl->getMethod("getInstance")->invoke(null);
    $callback = [$instance, $this->target];
    if (!is_callable($callback)) {
      throw new \BadFunctionCallException("Function {$this->target} does not exist on class {$this->targetClass}");
    }
    if ($this->args === null) {
      // Cannot be tested
      // @codeCoverageIgnoreStart
      call_user_func($callback);
      return;
      // @codeCoverageIgnoreEnd
    }
    if (!is_array($this->args)) {
      // Cannot be tested
      // @codeCoverageIgnoreStart
      call_user_func_array($callback, [$this->args]);
      return;
      // @codeCoverageIgnoreEnd
    }
    call_user_func_array($callback, [$this->args]);
  }
}
