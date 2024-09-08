<?php

namespace Weightless\Core\HTML\Elements;

use Weightless\Core\HTML\HTMLDocument;
use Weightless\Core\Logic\ClosureContainer;

class HTMLElement
{
  public string $tagName;
  /** @var array<string, string> */
  public array $attributes = [];
  /** @var HTMLElement[] */
  public array $children = [];
  public string $textContent = '';
  public HTMLElement | null $parentElement;
  public ClosureContainer $closureContainer;

  /** @param array<string, string> $attributes */
  public function __construct(string $tagName, public HTMLDocument | null &$document, array $attributes = [], string $textContent = "")
  {
    $this->tagName = $tagName;
    $this->attributes = $attributes;
    $this->textContent = $textContent;
    $this->closureContainer = new ClosureContainer($this);
  }

  public function appendChild(HTMLElement $child): void
  {
    $this->children[] = $child;
  }

  public function toString(): string
  {
    $attr_string = $this->formatAttributes();
    $children_string = $this->formatChildren();

    if (empty($this->children) && empty($this->textContent)) {
      return "<{$this->tagName}{$attr_string} />";
    }

    return "<{$this->tagName}{$attr_string}>{$children_string}{$this->textContent}</{$this->tagName}>";
  }

  protected function formatAttributes(): string
  {
    $parts = [];
    foreach ($this->attributes as $key => $value) {
      $parts[] = "{$key}=\"{$value}\"";
    }
    return $parts ? ' ' . implode(' ', $parts) : "";
  }

  protected function formatChildren(): string
  {
    $children_strings = [];
    foreach ($this->children as $child) {
      if ($this instanceof HTMLDocument) {
        $child->parentElement = null;
      } else {
        $child->parentElement = $this;
      }
      if ($child instanceof HTMLElement) {
        $children_strings[] = $child->toString();
      }
    }
    return implode('', $children_strings);
  }
}
