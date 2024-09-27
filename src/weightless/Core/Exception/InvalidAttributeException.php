<?php

namespace Weightless\Core\Exception;

class InvalidAttributeException extends \Exception {
  public function __construct(string $className)
  {
    $this->message = "Attribute $className does not exist";
  }
}

