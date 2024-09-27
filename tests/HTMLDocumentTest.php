<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Weightless\Core\HTML\HTMLDocument;
use Weightless\Core\Module\ViewModule;

class HTMLDocumentModule extends ViewModule
{
  public function __construct() {}

  public function build(): string
  {
    return "<a></a>";
  }
}

class HTMLDocumentTest extends TestCase
{
  #[Test]
  public function parseHtml()
  {
    $html = '#[Attribute("a", 5, ["foo", "bar"], named: "argument", ["associative" => "array"])]<head><title>Title</title></head><body><h1>Hello world!</h1><p><?php echo "Paragraph."; ?></p><module name="HTMLDocumentModule"></module><h2>{var}</h2></body>';
    $expected = '<head><title>Title</title></head><body><h1>Hello world!</h1><p>Paragraph.</p><a /><h2>Variable</h2></body>';

    $this->assertSame($expected, HTMLDocument::parse($html)->toString([
      "var" => "Variable"
    ]));

    $this->assertSame($expected, HTMLDocument::parseFile('data://text/plain,'.$html)->toString([
      "var" => "Variable"
    ]));

    HTMLDocument::parse($html)->echo([
      "var" => "Variable"
    ]);
    $this->expectOutputString("<script src='https://unpkg.com/htmx.org@2.0.2'></script>\n\n".$expected);
  }
}
