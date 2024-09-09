<?php

namespace Weightless\Core\Exception;

class InvalidClassNameException extends \Exception {
  public function __construct(string $className)
  {
    $this->message = "Class $className does not exist";
  }
}
