<?php

namespace Weightless\Core\HTML;

class HTMLFileAttribute
{
  const PREG_PATTERN = '/^(?!\s)#\[\s*(\w+)(?:\s*\((.*?)\))?\s*\]/';
  public string $name;
  /**
   * @var array<mixed>
   * */
  public array $args;

  /**
   * @var array<string, string>
   * */
  public static array $registeredAttributes;

  public function __construct(string $name, string $argsString)
  {
    $this->name = $name;
    $this->args = $this->parseArgs($argsString);
  }

  public function __set(mixed $name, mixed $value): void
  {
    $this[$name] = $value;
  }

  public function __get(mixed $name): mixed
  {
    return $this[$name];
  }

  /**
   * @return array<mixed>
   * */

  private function parseArgs(string $argsString): array
  {
    $args = [];

    if (empty($argsString)) {
      return $args;
    }

    // Regular expression to match named arguments, strings, arrays, associative arrays, or other simple arguments
    $pattern = '/
      (\w+:\s*)?               # Optional named argument (e.g., name: value)
      (\[[^\]]*\]) |           # Match arrays, including associative arrays
      ("[^"]*"|\'[^\']*\') |   # Match quoted strings
      ([^,\s]+)                # Match any other non-comma, non-whitespace sequence
    /x';

    if (preg_match_all($pattern, $argsString, $matches)) {
      foreach ($matches[0] as $arg) {
        $arg = trim($arg);

        if (strpos($arg, ':') !== false && preg_match('/^(\w+):\s*(.*)$/', $arg, $namedMatch)) {
          // Handle named arguments
          $key = $namedMatch[1];
          $value = trim($namedMatch[2]);

          // Handle value types for named arguments
          $args[$key] = $this->parseValue($value);
        } else {
          // Handle positional arguments
          $args[] = $this->parseValue($arg);
        }
      }
    }

    return $args;
  }

  private function parseValue(mixed $value): mixed
  {
    // Handle quoted strings
    if (preg_match('/^["\'](.*)["\']$/', $value, $strMatch)) {
      return $strMatch[1];  // Strip quotes and return the string value
    }

    // Handle arrays, including associative arrays
    if (preg_match('/^\[(.*)\]$/', $value, $arrMatch)) {
      $array = [];
      $elements = explode(',', $arrMatch[1]);

      foreach ($elements as $element) {
        $element = trim($element);
        if (strpos($element, '=>') !== false) {
          list($key, $val) = array_map('trim', explode('=>', $element, 2));
          $key = trim($key, "\"'");
          $array[$key] = $this->parseValue($val);
        } else {
          $array[] = $this->parseValue($element);
        }
      }

      return $array;
    }

    // Handle simple values (numbers, variables, etc.)
    return $value;
  }

  /**
   * @param array<mixed> $args 
   * */
  public function execute(array $args = []): mixed
  {
    if (!empty(HTMLFileAttribute::$registeredAttributes[$this->name])) {
      if (count($args) > 0) {
        return call_user_func_array(HTMLFileAttribute::$registeredAttributes[$this->name], $args);
      }
      return call_user_func_array(HTMLFileAttribute::$registeredAttributes[$this->name], $this->args);
    } else {
      trigger_error("HTMLFileAttribute {$this->name} is not defined or has not been registered correctly");
      return;
    }
  }


  /** 
   * @return HTMLFileAttribute[] */
  public static function parseFile(string $filePath): array
  {
    $content = file_get_contents($filePath);
    return self::parse($content);
  }

  /** 
   * @return HTMLFileAttribute[] */
  public static function parse(string $content): array
  {
    $attributes = [];

    // Regular expression to match attributes
    $pattern = '/^(?!\s)#\[\s*(\w+)(?:\s*\((.*?)\))?\s*\]/';

    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $name = $match[1];
        $argsString = $match[2] ?? '';

        $attributes[] = new self($name, $argsString);
      }
    }

    return $attributes;
  }

  public static function register(string $attributeName, \Closure $closure): void
  {
    self::$registeredAttributes[$attributeName] = $closure;
  }

  // public static function execute(HTMLFileAttribute $attribute, array $args = []): mixed {}
}
