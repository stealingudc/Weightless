<?php

namespace Weightless\Core\HTML\Elements;

use Weightless\Core\HTML\HTMLDocument;

class HTMLPHPElement extends HTMLElement
{
  public function __construct(string $code, public HTMLDocument | null &$document)
  {
    parent::__construct('php', $document, [], $code);
  }

  #[\Override]
  public function toString(): string
  {
    $code = $this->textContent;
    if ($this->parentElement === null) {
      return "";
    }
    $res = $this->parentElement->closureContainer->execute($code);
    if ($res === false) {
      // Will throw TypeError first
      // @codeCoverageIgnoreStart
      return "";
      // @codeCoverageIgnoreEnd
    }
    return $res;
  }
}
