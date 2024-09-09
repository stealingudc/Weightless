<?php

namespace Weightless\Core\ORM;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Type
{
    public function __construct(public string $rules) {}
}
