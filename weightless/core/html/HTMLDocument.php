<?php

namespace Weightless\Core\HTML;

use Weightless\Core\HTML\Elements\HTMLElement;
use Weightless\Core\HTML\Elements\HTMLPHPElement;
use Weightless\Core\HTML\Elements\HTMLTextElement;
use Weightless\Core\HTML\Elements\HTMLModuleElement;
use Weightless\Core\Logic\ClosureContainer;

class HTMLDocument extends HTMLElement
{
  public ClosureContainer $closureContainer;
  /** @var HTMLFileAttribute[] */
  public $fileAttributes = [];

  public function __construct()
  {
    $null = null;
    parent::__construct('document', attributes: [], textContent: "", document: $null);
    $this->closureContainer = new ClosureContainer($this);
  }

  public static function parse(string $html): HTMLDocument
  {
    $document = new self();
    $stack = [$document];

    $previousElement = &$document;

    if (preg_match_all(HTMLFileAttribute::PREG_PATTERN, $html, $matches, PREG_OFFSET_CAPTURE)) {
      $html = substr($html, $matches[0][count($matches[0]) - 1][1] + strlen($matches[0][count($matches[0]) - 1][0]));
      $document->fileAttributes = HTMLFileAttribute::parse($html);
    }

    // Decode HTML entities for correct parsing
    $tokens = preg_split('/(<\?php.*?\?>|<\/?\w+.*?>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    foreach ($tokens as $token) {
      if (preg_match('/^<\?php(.*?)\?>$/s', $token, $matches)) {
        $php_element = new HTMLPHPElement($matches[1], document: $document);
        $stack[count($stack) - 1]->appendChild($php_element);
      } else if (preg_match('/^<\/(\w+)>$/', $token, $matches)) {
        array_pop($stack);
      } else if (preg_match('/^<(\w+)(.*?)>$/s', $token, $matches)) {
        $tag_name = $matches[1];
        $attributes = self::parseAttributes($matches[2]);
        if ($tag_name === "module") {
          $element = new HTMLModuleElement($attributes, "", document: $document);
        } else {
          $element = new HTMLElement($tag_name, $attributes, "", document: $document);
        }
        $stack[count($stack) - 1]->appendChild($element);
        if (!in_array($tag_name, ['br', 'img', 'input', 'meta', 'hr', 'link'])) {
          $stack[] = $element;
        }
      } else {
        $text_element = new HTMLTextElement($token, $document);
        $stack[count($stack) - 1]->appendChild($text_element);
      }
    }

    return $document;
  }

  // Duplicate because passing $file_name to parse() may lead to unintended usage.
  public static function parseFile(string $file_name): HTMLDocument
  {
    $html = file_get_contents($file_name);

    $document = new self();
    $stack = [$document];

    $previousElement = &$document;

    if (preg_match_all(HTMLFileAttribute::PREG_PATTERN, $html, $matches, PREG_OFFSET_CAPTURE)) {
      $html = substr($html, $matches[0][count($matches[0]) - 1][1] + strlen($matches[0][count($matches[0]) - 1][0]));
      $document->fileAttributes = HTMLFileAttribute::parseFile($file_name);
    }

    // Decode HTML entities for correct parsing
    $tokens = preg_split('/(<\?php.*?\?>|<\/?\w+.*?>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    foreach ($tokens as $token) {
      if (preg_match('/^<\?php(.*?)\?>$/s', $token, $matches)) {
        $php_element = new HTMLPHPElement($matches[1], document: $document);
        $stack[count($stack) - 1]->appendChild($php_element);
      } else if (preg_match('/^<\/(\w+)>$/', $token, $matches)) {
        array_pop($stack);
      } else if (preg_match('/^<(\w+)(.*?)>$/s', $token, $matches)) {
        $tag_name = $matches[1];
        $attributes = self::parseAttributes($matches[2]);
        if ($tag_name === "module") {
          $element = new HTMLModuleElement($attributes, "", document: $document);
        } else {
          $element = new HTMLElement($tag_name, $attributes, "", document: $document);
        }
        $previousElement = &$element;
        $stack[count($stack) - 1]->appendChild($element);
        if (!in_array($tag_name, ['br', 'img', 'input', 'meta', 'hr', 'link'])) {
          $stack[] = $element;
        }
      } else {
        $text_element = new HTMLTextElement($token, $document);
        $stack[count($stack) - 1]->appendChild($text_element);
      }
    }

    return $document;
  }

  private static function parseAttributes(string $attribute_str)
  {
    $attributes = [];
    preg_match_all('/([\w-]+)\s*=\s*"([^"]*)"/', $attribute_str, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
      $attributes[$match[1]] = $match[2];
    }
    return $attributes;
  }

  public function toString(array $params = [])
  {
    $str = htmlspecialchars_decode($this->formatChildren(), ENT_NOQUOTES | ENT_HTML5);
    foreach($params as $key => $value){
      $str = str_replace("{".$key."}", $value, $str);
    }
    return $str;
  }

  public function echo(array $params = [])
  {
    echo "<script src='https://unpkg.com/htmx.org@2.0.2'></script>\n\n";
    echo $this->toString($params);
  }
}
