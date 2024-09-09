<?php

namespace Weightless\Core\Module;

use Weightless\Core\Module;

abstract class ViewModule implements Module {
  public string $textContent;
  abstract public function build(): string;
}
