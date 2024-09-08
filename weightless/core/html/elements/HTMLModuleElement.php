<?php

namespace Weightless\Core\HTML\Elements;

use Weightless\Core\HTML\Elements\HTMLElement;
use Weightless\Core\HTML\HTMLDocument;
use Weightless\Core\Module\ViewModule;

class HTMLModuleElement extends HTMLElement
{
  public function __construct(public HTMLDocument | null &$document, $attributes = [], public string $textContent = '')
  {
    parent::__construct('module', $document, $attributes, $textContent);
  }

  public function toString(): string
  {
    $this->formatChildren();
    $className = $this->attributes["name"] ?? "";
    if ($className === "") {
      trigger_error("Module has no name");
      return "";
    }
    $args = [];
    foreach ($this->attributes as $k => $attribute) {
      if ($k !== "name") {
        $args[] = $attribute;
      }
    }
    $refl = new \ReflectionClass($className);
    if (is_subclass_of($className, ViewModule::class)) {
      foreach ($refl->getConstructor()->getParameters() as $key => $param) {
        // @phpstan-ignore-next-line (See: https://github.com/phpstan/phpstan/issues/3937)
        $type = $param->getType()->getName();
        if ($type === "int") {
          $args[$key] = intval($args[$key]);
        }
      }
      $instance = $refl->newInstanceArgs($args);
      $instance->textContent = $this->textContent;
      $element = HTMLDocument::parse($instance->build());
      return $element->formatChildren();
    }
    return "";
  }
  protected function formatChildren(): string
  {
    $children_strings = [];
    foreach ($this->children as $child) {
      $child->parentElement = $this;
      if ($child instanceof HTMLPHPElement) {
        $children_strings[] = $child->toString();
      } else if ($child instanceof HTMLTextElement) {
        $this->textContent = htmlspecialchars_decode($child->toString(), ENT_QUOTES | ENT_HTML5);
      } else {
        trigger_error($child::class . " can not be a child of HTMLModuleElement");
      }
    }
    return implode('', $children_strings);
  }
}
