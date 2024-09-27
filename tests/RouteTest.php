<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Weightless\Core\Attribute\Route;
use Weightless\Core\Controller;

class RoutedController extends Controller
{
  #[Route("/test-route", ["GET", "POST"])]
  public function testFunctionWithRoute() {}
}

class RouteTest extends TestCase
{
  #[Test]
  public function testRoutedController() {
    Controller::register(RoutedController::class);
    $controller = RoutedController::getInstance();
    $controller->testFunctionWithRoute();
    $this->assertTrue(true);
  }

  #[Test]
  public function routeInvalidController() {
    $this->expectException(ReflectionException::class);
    $route = new Route("test", ["GET"]);
    $route->targetClass = "InvalidController";
    $route->target = "testFunctionWithRoute";
    $route->onFunctionCall();
  }

  #[Test]
  public function routeNonCallableCallback() {
    $this->expectException(BadFunctionCallException::class);
    $route = new Route("test", ["GET"]);
    $route->targetClass = "RoutedController";
    $route->target = "nonCallableCallback";
    $route->onFunctionCall();
  }
}
