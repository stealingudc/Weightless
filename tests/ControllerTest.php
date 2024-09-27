<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Weightless\Core\Controller;
use Weightless\Core\Exception\InvalidAttributeException;
use Weightless\Core\Exception\InvalidClassNameException;
use Weightless\Core\Logic\Singleton;

#[\Attribute]
class VanillaAttribute {}

class NonInheritingController extends Singleton {}

class InheritingController extends Controller {}

class ProblematicInheritingController extends Controller
{
  #[VanillaAttribute]
  public function testMethod() {}
}

final class ControllerTest extends TestCase
{
  #[Test]
  public function registerNonInheritingController()
  {
    $this->expectException(InvalidClassNameException::class);
    $this->expectExceptionMessage("NonInheritingController");
    Controller::register(NonInheritingController::class);
  }

  #[Test]
  public function getInstanceOfNonInheritingController(){
    $ctrl = Controller::getInstanceOf(NonInheritingController::class);
    $this->assertNull($ctrl);
  }

  #[Test]
  public function registerInheritingController()
  {
    Controller::register(InheritingController::class);
    $this->assertNotNull(Controller::getInstanceOf(InheritingController::class));
  }

  #[Test]
  public function registerProblematicController()
  {
    $this->expectException(InvalidAttributeException::class);
    Controller::register(ProblematicInheritingController::class);
    // $this->assertTrue(null !== Controller::getInstanceOf(ProblematicInheritingController::class));
  }

  #[Test]
  public function getInstanceOfController()
  {
    $this->expectException(InvalidClassNameException::class);
    Controller::getInstanceOf("ThisControllerDoesNotExist");
    $this->assertSame(null, Controller::getInstanceOf(NonInheritingController::class));
  }
}
