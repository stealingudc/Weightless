<?php

namespace Weightless\Core\HTML;

use Weightless\Core\Exception\InvalidClassNameException;

class HTMLFileAttribute
{
  const PREG_PATTERN = '/^(?!\s)#\[\s*(\w+)(?:\s*\((.*?)\))?\s*\]/';
  /**
   * @var array<mixed>
   * */
  public array $args;

  /**
   * @var array<string, \Closure|string>
   * */
  public static array $registeredAttributes;

  public function __construct(public string $name, string $argsString)
  {
    $this->args = $this->parseArgs($argsString);
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

        if (str_contains($arg, ':') && preg_match('/^(\w+):\s*(.*)$/', $arg, $namedMatch)) {
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

  private function parseValue(string $value): mixed
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
        if (str_contains($element, '=>')) {
          [$key, $val] = array_map('trim', explode('=>', $element, 2));
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
    if (empty(HTMLFileAttribute::$registeredAttributes[$this->name])) {
      throw new InvalidClassNameException("HTMLFileAttribute {$this->name} is not defined or has not been registered correctly");
    }
    $callback = HTMLFileAttribute::$registeredAttributes[$this->name];
    // Will always be callable, since register() will throw TypeError if not
    // @codeCoverageIgnoreStart
    if (!is_callable($callback)) {
      throw new \BadFunctionCallException("Function $callback does not exist or is not callable");
    }
    // @codeCoverageIgnoreEnd
    if (count($args) > 0) {
      return call_user_func_array($callback, $args);
    }
    return call_user_func_array($callback, $this->args);
  }


  /** 
   * @return HTMLFileAttribute[] */
  public static function parseFile(string $filePath): array
  {
    $content = file_get_contents($filePath);
    if($content === false){
      return self::parse("");
    }
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
