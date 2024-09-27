<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Weightless\Core\Logic\ClosureContainer;

class BindToMe {

}

class ClosureContainerTest extends TestCase
{
  #[Test]
  public function closureContainer()
  {
    $code = "
      use Weightless\\Core\\Logic\\ClosureContainer;\n

      \$var = 'Hello world!';\n
      echo \$var;
    ";

    $bindToMe = new BindToMe();
    $container = new ClosureContainer($bindToMe);

    $result = $container->execute($code);
    $this->assertSame("Hello world!", $result);

    //Code with error
    $code = "
      return explode(echo var)
    ";

    $container->execute($code);
    $this->expectOutputString("");
  }
}
