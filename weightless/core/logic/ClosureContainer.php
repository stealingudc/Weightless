<?php

namespace Weightless\Core\Logic;

/** Captures any plaintext closure(s) within the same context and binds them to the parent class. 
 * To achieve this, the class must declare a member of type ClosureContainer and initialize it with new ClosureContainer($this).
 * This does NOT work with Closure objects. */
class ClosureContainer
{
  private $variables = [];
  private $imports = [];

  public function __construct(public object $obj) {}

  /**
   * Parses plaintext PHP code, extracts declared variables and stores them to be used when $this->execute() is called.
   *
   * @param string $code - The plaintext PHP code.
   */
  private function appendPlaintextVars(string $code)
  {
    // Magic regex. Please don't ask - it just works.
    $code = trim($code);
    preg_match_all('/(?:require(?:_once)?\s.*?;\s*|use\s[^;]+;\s*|\$(\w+)\s*=\s*(.*?);)/', $code, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
      if (strpos($match[0], 'require') !== false || strpos($match[0], 'use') !== false) {
        // This is an import statement (either `require`, `require_once`, or `use`)
        $this->imports[] = trim($match[0]);
      }
      $require = $match[0] ?? '';
      $use = $match[1] ?? '';
      $var_name = $match[2] ?? null;
      $var_value_code = $match[3] ?? null;

      $this->variables[$var_name] = eval(($require ?? "") . ($use ?? "") . 'return ' . $var_value_code . ';');
    }


    $code = preg_replace('/(?:require_once\s.*?;\s*|use\s[^;]+;\s*)/', "", $code);

    return $code;
  }

  /**
   * Executes plaintext PHP code as a closure, similarly to eval(), while making use of variables previously appended to this ClosureContainer. 
   *
   * @param string $code - The plaintext PHP code.
   * @return mixed
   */
  public function execute(string $code)
  {
    $code = $this->appendPlaintextVars($code);
    $imports_str = "";
    foreach ($this->imports as $import) {
      $imports_str .= $import;
    }
    extract($this->variables);
    $closure = eval($imports_str . 'return function(){' . $code . '};');
    $bound = \Closure::bind($closure, $this->obj);

    return $bound();
  }
}
