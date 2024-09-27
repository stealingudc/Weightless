<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Weightless\Core\Exception\InvalidClassNameException;
use Weightless\Core\HTML\HTMLFileAttribute;

class HTMLFileAttributeTest extends TestCase {
  #[Test]
  public function parse() {
    $attr = HTMLFileAttribute::parse('#[Attribute("a", 5, ["foo", "bar"], named: "argument", ["associative" => "array"])]');

    $this->assertSame(['a', '5', ['foo', 'bar'], 'named' => '', 'argument', ['associative' => 'array']], $attr[0]->args);

    $noArgs = HTMLFileAttribute::parse('#[Attribute()]');
    
    $this->assertSame([], $noArgs[0]->args);

    $invalidFile = HTMLFileAttribute::parseFile("file/that/does/not/exist");

    $this->assertSame($invalidFile[0], null);

    $registered = HTMLFileAttribute::parse('#[RegisteredAttribute()]')[0];

    HTMLFileAttribute::register("RegisteredAttribute", function(){
      echo "Hello world!";
    });

    $this->expectOutputString("Hello world!");
    $registered->execute();

    HTMLFileAttribute::register("RegisteredAttributeWithArgs", function(string $user){
      return "Hello $user!";
    });

    $registeredWithArgs = HTMLFileAttribute::parse('#[RegisteredAttributeWithArgs("user")]')[0];
    $this->assertSame("Hello user!", $registeredWithArgs->execute(["user"]));

  }

  #[Test]
  public function invalidClassNameAttribute(){
    $invalidClassName = HTMLFileAttribute::parse('#[InvalidClassNameAttribute()]')[0];
    $this->expectException(InvalidClassNameException::class);
    $invalidClassName->execute();
  }
}
