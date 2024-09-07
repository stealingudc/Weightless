<?php

namespace Weightless\Core;

use Weightless\Core\HTML\HTMLDocument;
use Weightless\Core\HTML\HTMLFileAttribute;

class Weightless
{
  public static function init()
  {
    Router::getInstance()->set404(function () {
      header("HTTP/1.1 404 Not Found");

      HTMLDocument::parseFile("views/404.wl.php")->echo();
    });

    HTMLFileAttribute::register("Route", function (string $route, callable $closure, array $methods) {
      Router::getInstance()->match($methods, $route, $closure);
    });
    self::loadControllers();
    self::loadRuntimeModules();
    self::loadViews();
    Router::getInstance()->run();
  }

  private static function loadControllers()
  {
    $dir_iter = new \RecursiveDirectoryIterator("controllers");
    $iter = new \RecursiveIteratorIterator($dir_iter);
    $preg_iter = new \RegexIterator($iter, '/\.php$/');
    $preg_iter->setFlags(\RegexIterator::USE_KEY);

    $classes_before = get_declared_classes();
    foreach ($preg_iter as $file) {
      $path = $file->getPathname();
      if (substr($path, -4) === ".php" && substr($path, -7) !== ".wl.php") {
        require_once $_SERVER["DOCUMENT_ROOT"] . "/$path";
      }
    }
    $classes_after = get_declared_classes();

    $new_classes = array_diff($classes_after, $classes_before);

    foreach ($new_classes as $class_name) {
      if (is_subclass_of($class_name, "Weightless\\Core\\Controller")) {
        $controller = call_user_func("$class_name::getInstance");
        $refl = new \ReflectionClass($controller);

        foreach ($refl->getMethods() as $method) {
          $attributes = $method->getAttributes();
          if (count($attributes) > 0) {
            $methodName = $method->getName();
            foreach ($attributes as $attribute) {
              $instance = $attribute->newInstance();
              $instance->targetClass = $controller;
              $instance->target = $methodName;
              $instance->execute();
            }
          }
        }
      }
    }
  }

  private static function loadRuntimeModules()
  {
    $dir_iter = new \RecursiveDirectoryIterator("modules");
    $iter = new \RecursiveIteratorIterator($dir_iter);
    $preg_iter = new \RegexIterator($iter, '/\.php$/');
    $preg_iter->setFlags(\RegexIterator::USE_KEY);

    foreach ($preg_iter as $file) {
      $path = $file->getPathname();

      $classes_before = get_declared_classes();
      if (substr($path, -4) === ".php" && substr($path, -7) !== ".wl.php") {
        require_once $_SERVER["DOCUMENT_ROOT"] . "/$path";
      }
      $classes_after = get_declared_classes();

      $new_classes = array_diff($classes_after, $classes_before);


      foreach ($new_classes as $class_name) {
        if (is_subclass_of($class_name, "Weightless\\Core\\Module\\RuntimeModule")) {
          $module = call_user_func("$class_name::getInstance");
          $module->build();
        }
      }
    }
  }

  private static function loadViews()
  {

    $dir_iter = new \RecursiveDirectoryIterator("views");
    $iter = new \RecursiveIteratorIterator($dir_iter);
    $preg_iter = new \RegexIterator($iter, '/\.wl.php$/');
    $preg_iter->setFlags(\RegexIterator::USE_KEY);

    foreach ($preg_iter as $file) {
      $path = $file->getPathname();
      $attributes = HTMLFileAttribute::parseFile($_SERVER["DOCUMENT_ROOT"] . "/$path");
      foreach ($attributes as $attribute) {
        if ($attribute->name === "Route") {
          $attribute->execute([$attribute->args[0], function () use ($path) {
            $document = HTMLDocument::parseFile($path);
            echo $document->toString();
          }, $attribute->args["methods"] ?? $attribute->args[1]]);
        }
      }
    }
  }
}
