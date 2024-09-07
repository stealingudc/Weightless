<?php

namespace Weightless\Core\Attribute;

use Weightless\Core\Router;

#[\Attribute]
class Route extends Attribute
{
  public function __construct(public string $url, public array $methods) {}

  public function execute()
  {
    $obj = $this;
    $closure = function ($args = null) use ($obj) {
      if($args === null){
        call_user_func([$obj->targetClass, $obj->target]);
      }
      if (!is_array($args)) {
        call_user_func_array([$obj->targetClass, $obj->target], [$args]);
        return;
      }
      call_user_func_array([$obj->targetClass, $obj->target], [$args]);
    };
    Router::getInstance()->match($this->methods, $this->url, $closure);
  }
}
