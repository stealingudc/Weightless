<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Weightless\Core\Attribute\Attribute;
use Weightless\Core\Attribute\Route;
use Weightless\Core\Controller;

class TestAttribute extends Attribute
{
  public array $messages = [];
  public function onFunctionCall(): void
  {
    echo $this->targetClass;
    echo $this->target;
  }
}

class ControllerWithAttribute extends Controller
{
  #[Attribute("TestAttribute")]
  public static function attributeFunction()
  {
    return "Hello, world!";
  }
}

class ControllerWithRoute extends Controller
{
  #[Route("/controller-with-route", ["GET", "POST"])]
  public static function attributeFunction()
  {
    return "Hello, world!";
  }
}


final class AttributeTest extends TestCase
{
  #[Test]
  public function executeAttribute()
  {
    Controller::register(ControllerWithAttribute::class);
    ControllerWithAttribute::attributeFunction();
    $this->expectOutputString("ControllerWithAttribute" . "attributeFunction");
  }

  #[Test]
  public function executeAttributeDirectly(){
    $attribute = new TestAttribute("TestAttribute");
    $this->expectException(Error::class);
    $attribute->onFunctionCall();
  }

  #[Test]
  public function executeNonExistingAttribute(){
    $attribute = new Attribute("NonExistingAttribute");
    $attribute->targetClass = "ControllerWithAttribute";
    $attribute->target = "attributeFunction";
    $this->expectException(ReflectionException::class);
    $this->expectExceptionMessage("NonExistingAttribute");
    $attribute->execute();
  }

  #[Test]
  public function executeNonCallableCallbackAttribute(){
    $attribute = new Attribute(TestAttribute::class);
    $attribute->targetClass = "ControllerWithAttribute";
    $attribute->target = "nonExistingFunction";
    $this->expectException(ReflectionException::class);
    $this->expectExceptionMessage("nonExistingFunction");
    $attribute->execute();
  }

  #[Test]
  public function onAttributeFunctionCall(){
    $attribute = new Attribute("TestAttribute");
    $attribute->onFunctionCall();
    $this->assertTrue(true);
  }
}
