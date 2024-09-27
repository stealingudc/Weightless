<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Weightless\Core\Exception\InvalidClassNameException;
use Weightless\Core\Exception\InvalidModuleException;
use Weightless\Core\HTML\Elements\HTMLElement;
use Weightless\Core\HTML\Elements\HTMLModuleElement;
use Weightless\Core\HTML\Elements\HTMLPHPElement;
use Weightless\Core\HTML\Elements\HTMLTextElement;
use Weightless\Core\Module\ViewModule;

class HTMLTestModule extends ViewModule
{
  public function __construct() {}

  public function build(): string
  {
    return "<h1>Hello world!</h1>";
  }
}

class HTMLTestModuleWithArgs extends ViewModule
{
  public function __construct(public string $username, public int $times) {}

  public function build(): string
  {
    $out = '';
    for ($i = 0; $i < $this->times; $i++) {
      $out .= "<h1>Hello, $this->username!</h1>";
    }
    return $out;
  }
}

class HTMLNonInheritingTestModule {}

class HTMLElementsTest extends TestCase
{
  #[Test]
  public function htmlElement()
  {
    $null = null;

    $element = new HTMLElement(
      tagName: 'div',
      document: $null,
      attributes: [
        'class' => 'test-div'
      ]
    );

    $this->assertSame('<div class="test-div" />', $element->toString());

    $element->appendChild(
      new HTMLElement(
        tagName: 'p',
        document: $null,
        textContent: 'Hello world!'
      )
    );

    $this->assertSame('<div class="test-div"><p>Hello world!</p></div>', $element->toString());
  }

  #[Test]
  public function phpElement()
  {
    $null = null;

    $element = new HTMLPHPElement(
      code: "echo 'Hello world!';",
      document: $null
    );

    $this->assertSame('', $element->toString());

    $parentElement = new HTMLElement(
      tagName: 'div',
      document: $null
    );

    $parentElement->appendChild($element);
    $element->parentElement = $parentElement;

    $this->assertSame('Hello world!', $element->toString());
  }

  #[Test]
  public function textElement()
  {
    $null = null;

    $element = new HTMLTextElement(
      textContent: 'Hello world!',
      document: $null
    );

    $this->assertSame('Hello world!', $element->toString());
  }

  #[Test]
  public function moduleElement()
  {
    $null = null;

    $element = new HTMLModuleElement($null);

    $this->assertSame('', $element->toString());

    $element->attributes = [
      "name" => "HTMLTestModule"
    ];

    $this->assertSame('<h1>Hello world!</h1>', $element->toString());

    $elementWithArgs = new HTMLModuleElement($null, [
      "name" => "HTMLTestModuleWithArgs",
      "username" => "John Doe",
      "times" => "1"
    ]);

    $this->assertSame('<h1>Hello, John Doe!</h1>', $elementWithArgs->toString());

    $codeElement = $element;

    $codeElement->appendChild(
      new HTMLPHPElement(document: $null, code: "return 'Hello world!';")
    );

    $this->assertSame('<h1>Hello world!</h1>', $codeElement->toString());

    $textElement = $element;

    $textElement->appendChild(
      new HTMLTextElement(document: $null, textContent: "Sample Text")
    );

    $this->assertSame("<h1>Hello world!</h1>", $textElement->toString());

    $invalidChildElement = $element;

    $invalidChildElement->appendChild(
      new HTMLElement(
        tagName: "div",
        document: $null
      )
    );

    $this->assertSame('<h1>Hello world!</h1>', $invalidChildElement->toString());

    $nonInheritingElement = new HTMLModuleElement($null, [
      "name" => "HTMLNonInheritingTestModule"
    ]);

    $this->expectException(InvalidModuleException::class);
    $nonInheritingElement->toString();

    $wrongElement = new HTMLModuleElement($null, [
      "name" => "ThisClassDoesNotExist"
    ]);

    $this->expectException(InvalidClassNameException::class);
    $wrongElement->toString();
  }

  #[Test]
  public function moduleElementNotExists(){
    $null = null;

    $wrongElement = new HTMLModuleElement($null, [
      "name" => "ThisClassDoesNotExist"
    ]);

    $this->expectException(InvalidClassNameException::class);
    $wrongElement->toString();
  }
}
