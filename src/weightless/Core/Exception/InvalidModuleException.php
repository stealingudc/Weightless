<?php

namespace Weightless\Core\Exception;

class InvalidModuleException extends \Exception {
  public function __construct(string $className)
  {
    $this->message = "Module $className does not exist";
  }
}
