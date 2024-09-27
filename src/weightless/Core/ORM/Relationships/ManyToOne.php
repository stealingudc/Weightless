<?php

namespace Weightless\Core\ORM\Relationships;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ManyToOne
{
  // @codeCoverageIgnoreStart
  public function __construct(public string $targetEntity, public string $inversedBy) {}
  // @codeCoverageIgnoreEnd
}
