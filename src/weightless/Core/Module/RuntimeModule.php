<?php

namespace Weightless\Core\Module;

use Weightless\Core\Module;
use Weightless\Core\Logic\Singleton;

abstract class RuntimeModule extends Singleton implements Module
{
  // Cannot test unimplemented method
  // @codeCoverageIgnoreStart
  public abstract function onPageLoad(): void;
  // @codeCoverageIgnoreEnd
}
