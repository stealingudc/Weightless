<?php

namespace Weightless\Core\ORM;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Column
{
  public function __construct(public string $name) {}
}
