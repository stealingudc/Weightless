<?php

namespace Weightless\Core\Module;

use Weightless\Core\Module;

abstract class ViewModule implements Module {
  public string $textContent;
  // Cannot test unimplemented method
  // @codeCoverageIgnoreStart
  abstract public function build(): string;
  // @codeCoverageIgnoreEnd
}
