<?php

namespace Weightless\Core\HTML\Elements;

use Weightless\Core\HTML\HTMLDocument;

class HTMLPHPElement extends HTMLElement
{
  public function __construct(string $code, public HTMLDocument | null &$document)
  {
    parent::__construct('php', $document, [], $code);
  }

  public function toString(): string{
    $code = $this->textContent;
    return $this->parentElement->closureContainer->execute($code) ?? "";
  }
}
