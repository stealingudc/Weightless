<?php

namespace Weightless\Core\HTML\Elements;

use Weightless\Core\HTML\HTMLDocument;

class HTMLTextElement extends HTMLElement
{
  public function __construct(string $textContent, HTMLDocument | null &$document)
  {
    parent::__construct('#text', [], $textContent, $document);
  }

  public function toString()
  {
    return htmlentities($this->textContent);
  }
}
