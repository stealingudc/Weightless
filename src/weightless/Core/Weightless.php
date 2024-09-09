<?php

namespace Weightless\Core;

use Weightless\Core\Attribute\Attribute;
use Weightless\Core\Exception\InvalidClassNameException;
use Weightless\Core\HTML\HTMLDocument;
use Weightless\Core\HTML\HTMLFileAttribute;
use Weightless\Core\Module\RuntimeModule;

class Weightless
{
  public static function init(): void
  {
    Router::getInstance()->set404(function (): void {
      header("HTTP/1.1 404 Not Found");

      HTMLDocument::parseFile("views/404.wl.php")->echo();
    });

    HTMLFileAttribute::register("Route", function (string $route, callable $closure, array $methods): void {
      Router::getInstance()->match($methods, $route, $closure);
    });
    self::loadControllers();
    self::loadRuntimeModules();
    self::loadViews();
    Router::getInstance()->run();
  }

  private static function loadControllers(): void
  {
    $dir_iter = new \RecursiveDirectoryIterator(dirname((string) $_SERVER["DOCUMENT_ROOT"]) . "/src/controllers");
    $iter = new \RecursiveIteratorIterator($dir_iter);
    $preg_iter = new \RegexIterator($iter, '/\.php$/');
    $preg_iter->setFlags(\RegexIterator::USE_KEY);

    $classes_before = get_declared_classes();
    foreach ($preg_iter as $file) {
      $path = "";
      if ($file instanceof \SplFileInfo) {
        $path = $file->getPathname();
      }
      if (str_ends_with((string) $path, ".php") && !str_ends_with((string) $path, ".wl.php")) {
        require_once $path;
      }
    }
    $classes_after = get_declared_classes();

    $new_classes = array_diff($classes_after, $classes_before);

    foreach ($new_classes as $class_name) {
      if (!class_exists($class_name)) {
        throw new InvalidClassNameException($class_name);
      }
      if (is_subclass_of($class_name, \Weightless\Core\Controller::class)) {
        $refl = new \ReflectionClass($class_name);

        foreach ($refl->getMethods() as $method) {
          $attributes = $method->getAttributes();
          if (count($attributes) > 0) {
            $methodName = $method->getName();
            foreach ($attributes as $attribute) {
              $instance = $attribute->newInstance();
              if ($instance instanceof Attribute) {
                $controller = $refl->getMethod("getInstance")->invoke(null);
                if ($controller instanceof Controller) {
                  $instance->targetClass = $controller::class;
                  $instance->target = $methodName;
                  $instance->execute();
                }
              }
            }
          }
        }
      }
    }
  }

  private static function loadRuntimeModules(): void
  {
    $dir_iter = new \RecursiveDirectoryIterator(dirname((string) $_SERVER["DOCUMENT_ROOT"]) . "/src/controllers");
    $iter = new \RecursiveIteratorIterator($dir_iter);
    $preg_iter = new \RegexIterator($iter, '/\.php$/');
    $preg_iter->setFlags(\RegexIterator::USE_KEY);
    $path = "";

    foreach ($preg_iter as $file) {
      if ($file instanceof \SplFileInfo) {
        $path = $file->getPathname();
      }

      $classes_before = get_declared_classes();
      if (str_ends_with((string) $path, ".php") && !str_ends_with((string) $path, ".wl.php")) {
        require_once $path;
      }
      $classes_after = get_declared_classes();

      $new_classes = array_diff($classes_after, $classes_before);


      foreach ($new_classes as $class_name) {
        if (is_subclass_of($class_name, \Weightless\Core\Module\RuntimeModule::class)) {
          $refl = new \ReflectionClass($class_name);
          $instance = $refl->getMethod("getInstance")->invoke(null);
          if ($instance instanceof RuntimeModule) {
            $instance->build();
          }
        }
      }
    }
  }

  private static function loadViews(): void
  {
    $dir_iter = new \RecursiveDirectoryIterator($_SERVER["DOCUMENT_ROOT"] . "/views");
    $iter = new \RecursiveIteratorIterator($dir_iter);
    $preg_iter = new \RegexIterator($iter, '/\.wl.php$/');
    $preg_iter->setFlags(\RegexIterator::USE_KEY);
    $path = "";

    foreach ($preg_iter as $file) {
      if ($file instanceof \SplFileInfo) {
        $path = $file->getPathname();
      }
      $attributes = HTMLFileAttribute::parseFile($path);
      foreach ($attributes as $attribute) {
        if ($attribute->name === "Route") {
          $attribute->execute([$attribute->args[0], function () use ($path): void {
            $document = HTMLDocument::parseFile($path);
            echo $document->toString();
          }, $attribute->args["methods"] ?? $attribute->args[1]]);
        }
      }
    }
  }
}
