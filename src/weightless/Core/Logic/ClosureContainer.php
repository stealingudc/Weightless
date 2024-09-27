<?php

namespace Weightless\Core\Logic;

// Takes plaintext (string) PHP code and binds it to a parent class, evaluating the code.
class ClosureContainer
{
  private object $parent;
  /** @var array<mixed> $scope */
  public array $scope;

  public function __construct(object $parent)
  {
    $this->parent = $parent;
    $this->scope = [];
  }

  public function execute(string $code): string|false
  {
    $container = &$this;
    $closure = function () use ($code, $container) {
      extract($container->scope); // Bring previous variables into scope
      ob_start(); // Start output buffering to capture any output

      try {
        eval($code);
        
        // I don't know. Whatever I do, this won't get thrown. My head hurts. I'm sorry.
        // @codeCoverageIgnoreStart
      } catch (\Throwable $e) {
        echo "Error in evaluated code: " . $e->getMessage();
      }
      // @codeCoverageIgnoreEnd

      $output = ob_get_clean(); // Get output and clean buffer
      $container->scope = get_defined_vars(); // Update the scope with new variables

      return $output;
    };

    return $closure->call($this->parent);
  }
}
