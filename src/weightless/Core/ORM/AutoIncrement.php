<?php

namespace Weightless\Core\ORM;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class AutoIncrement
{
    public function __construct(public bool $enabled = true) {}
}
