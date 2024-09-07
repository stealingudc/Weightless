<?php

namespace Weightless\Core\Attribute;

use ReflectionClass;
use Weightless\Core\Router as Router;

#[\Attribute]
class Attribute
{
  public $targetClass;
  public mixed $target;
  public string $attributeName;
  public $args;

  public function __construct(string $attributeName, ...$args)
  {
    $this->attributeName = $attributeName;
    $this->args = $args;
  }

  public function execute()
  {
    // TODO: clean this up, lol
    $targetClassRefl = new ReflectionClass($this->targetClass);
    $routeArgs = [];
    $method = $targetClassRefl->getMethod($this->target);
    $attrs = $method->getAttributes();
    foreach ($attrs as $attr) {
      if ($attr->getName() === "Weightless\\Core\\Attribute\\Route") {
        $routeArgs = $attr->getArguments();
        break;
      }
    }

    if (count($routeArgs) > 0) {
      $obj = $this;
      $closure = function () use ($obj) {
        call_user_func([$obj->targetClass, $obj->target]);
        $refl = new ReflectionClass($this->attributeName);
        $refl->newInstance($this->args)->execute();
      };

      echo "<script src='https://unpkg.com/htmx.org@2.0.2'></script>";
      Router::getInstance()->match($routeArgs["methods"] ?? $routeArgs[1], $routeArgs[0], $closure);
      // Router::routeMethods($routeArgs[0], $closure, $routeArgs["methods"] ?? $routeArgs[1]);
    }
  }
}
