<?php

namespace Weightless\Core\ORM\Relationships;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class OneToMany
{
    public function __construct(public string $targetEntity, public string $mappedBy) {}
}
