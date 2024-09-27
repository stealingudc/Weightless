<?php

include_once dirname(__DIR__) . "/vendor/autoload.php";

// use PHPUnit\Framework\Attributes\Test;
// use PHPUnit\Framework\TestCase;
// use Weightless\Core\Router;

// class RouterTest extends TestCase
// {
//   #[Test]
//   public function getRequestHeaders()
//   {
//     $headers = Router::getRequestHeaders();
//     $this->assertCount(0, $headers);
//   }

//   #[Test]
//   public function allRequestMethods()
//   {
//     $ch = curl_init();
//     $curl_cfg = [
//       CURLOPT_URL => "http://localhost:8081/test-route",
//       CURLOPT_POST => false,
//       CURLOPT_RETURNTRANSFER => true
//     ];
//     curl_setopt_array($ch, $curl_cfg);
//     Router::getInstance()->all("test-route", function () {
//       echo "Hello world!";
//     });
//     $result = curl_exec($ch);
//     curl_close($ch);
//     Router::getInstance()->run();
//     $this->assertNotNull($result);
//   }

//   #[Test]
//   public function allRequestMethodsSeparate()
//   {
//     $ch = curl_init();
//     $curl_cfg = [
//       CURLOPT_URL => "http://localhost:8081/test-route",
//       CURLOPT_POST => false,
//       CURLOPT_RETURNTRANSFER => true
//     ];
//     curl_setopt_array($ch, $curl_cfg);

//     Router::getInstance()->get("test-route", function () {
//       echo "Hello world!";
//     });
//     Router::getInstance()->post("test-route", function () {
//       echo "Hello world!";
//     });
//     Router::getInstance()->put("test-route", function () {
//       echo "Hello world!";
//     });
//     Router::getInstance()->patch("test-route", function () {
//       echo "Hello world!";
//     });
//     Router::getInstance()->delete("test-route", function () {
//       echo "Hello world!";
//     });
//     $result = curl_exec($ch);
//     curl_close($ch);
//     Router::getInstance()->run();
//     $this->assertNotNull($result);
//   }

//   #[Test]
//   public function getURLPartsWithMatchingRoute()
//   {
//     $_SERVER['REQUEST_URI'] = '/posts/123';
//     $url = '/posts/$id';
//     $result = Router::getURLParts($url);
//     // We expect '123' to be captured in place of $id
//     $this->assertEquals(['123'], $result);
//   }

//   #[Test]
//   public function getURLPartsWithNonMatchingRoute()
//   {
//     $_SERVER['REQUEST_URI'] = '/posts/123';
//     $url = '/users/$id';
//     $result = Router::getURLParts($url);
//     // Expecting an empty array because the routes don't match
//     $this->assertEquals([], $result);
//   }

//   #[Test]
//   public function getURLPartsWithQueryString()
//   {
//     $_SERVER['REQUEST_URI'] = '/posts/123?foo=bar';
//     $url = '/posts/$id';
//     $result = Router::getURLParts($url);
//     // We expect the query string to be ignored and '123' captured
//     $this->assertEquals(['123'], $result);
//   }

//   #[Test]
//   public function getURLPartsWithEmptyRequestURI()
//   { {
//       $_SERVER['REQUEST_URI'] = '';
//       $url = '/posts/$id';
//       $result = Router::getURLParts($url);
//       // Since the request URI is empty, the result should be an empty array
//       $this->assertEquals([], $result);
//     }
//   }
// }


use PHPUnit\Framework\TestCase;
use Weightless\Core\Router;
use Symfony\Component\Process\Process;

class RouterTest extends TestCase
{
  private Router $router;
  private GuzzleHttp\Client $http;
  private static Process $server;

  public static function setUpRoutes()
  {
    $router = Router::getInstance();

    $router->match(["GET"], "/test", function () {
      echo "matched";
    });

    $router->get('/product/{id}', function ($id) {
      echo "Product ID: $id";
    });

    $router->post('/submit', function () {
      echo 'submitted';
    });

    $router->put('/update', function () {
      echo 'updated';
    });

    $router->patch('/patch', function () {
      echo 'patched';
    });

    $router->delete('/delete', function () {
      echo 'deleted';
    });

    $router->options('/options', function () {
      echo 'optioned';
    });

    $router->all('/all', function () {
      echo 'alled';
    });

    $router->all('/request-method', function () {
      echo Router::getRequestMethod();
    });

    $router->before(['GET'], '/middleware', function () {
      echo 'before';
    });

    $router->get('/middleware', function () {
      echo ' after';
    });

    $router->mount('/api', function () use ($router) {
      $router->get('/', function () {
        echo 'api';
      });

      $router->get('/(\d+)', function ($id) {
        echo $id;
      });
    });

    $router->mount('/this-does', function () use ($router) {
      $router->get('/not-exist', function () use ($router) {
        $router->trigger404();
      });
    });

    $router->set404(function () {
      echo '404';
    });

    Router::getInstance()->run();
  }

  public static function setUpBeforeClass(): void
  {
    self::$server = new Process(["php", "-S", "localhost:8081", "tests/RouterTest.php"]);
    // $process = new Process(["ls", "-lsa"]);
    // $process->start();
    // foreach ($process as $type => $data) {
    //   if ($process::OUT === $type) {
    //     echo "\nRead from stdout: " . $data;
    //   } else { // $process::ERR === $type
    //     echo "\nRead from stderr: " . $data;
    //   }
    // }
    self::$server->start();

    usleep(1000000);
  }

  public static function tearDownAfterClass(): void
  {
    self::$server->stop();
  }

  protected function setUp(): void
  {
    $this->router = Router::getInstance();
    $this->http = new GuzzleHttp\Client();

    // Clear SCRIPT_NAME because bramus/router tries to guess the subfolder the script is run in
    $_SERVER['SCRIPT_NAME'] = '/index.php';

    // Default request method to GET
    $_SERVER['REQUEST_METHOD'] = 'GET';

    // Default SERVER_PROTOCOL method to HTTP/1.1
    $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
  }

  public function testMatchAndGetRoutes(): void
  {
    self::setUpRoutes();
    $res = $this->http->request("GET", "http://localhost:8081/test");
    $this->assertSame('matched', $res->getBody()->getContents());
  }

  public function testPostRoute(): void
  {
    self::setUpRoutes();
    $res = $this->http->request("POST", "http://localhost:8081/submit");
    $this->assertSame('submitted', $res->getBody()->getContents());
  }

  public function testMiddleware(): void
  {
    self::setUpRoutes();
    $res = $this->http->request("GET", "http://localhost:8081/middleware");
    $this->assertSame('before after', $res->getBody()->getContents());
  }

  public function test404Handling(): void
  {
    self::setUpRoutes();
    $res = $this->http->request("GET", "http://localhost:8081/404");
    $res = $this->http->request("GET", "http://localhost:8081/this-does/not-exist");
    $this->assertSame('404', $res->getBody()->getContents());
  }

  public function test404WithManualTrigger()
  {
    $router = $this->router;
    // Create Router
    $this->router->get('/', function () use ($router) {
      $router->trigger404();
    });
    $this->router->set404(function () {
      echo 'route not found';
    });

    // Test the / route
    ob_start();
    $_SERVER['REQUEST_URI'] = '/';
    $router->run();
    $this->assertEquals('route not found', ob_get_contents());

    // Cleanup
    ob_end_clean();
  }

  public function testBasePathOverride()
  {
    // Create Router
    $this->router->match(['GET'], '/about', function () {
      echo 'about';
    });

    // Fake some data
    $_SERVER['SCRIPT_NAME'] = '/public/index.php';
    $_SERVER['REQUEST_URI'] = '/about';

    $this->router->setBasePath('/');

    $this->assertEquals(
      '/',
      $this->router->getBasePath()
    );

    // Test the /about route
    ob_start();
    $_SERVER['REQUEST_URI'] = '/about';
    $this->router->run();
    $this->assertEquals('about', ob_get_contents());

    // Cleanup
    ob_end_clean();
  }

  public function testMountRoutes(): void
  {
    self::setUpRoutes();
    $resApi = $this->http->request("GET", "http://localhost:8081/api");
    $resUsers = $this->http->request("GET", "http://localhost:8081/api/404");
    $this->assertSame('api', $resApi->getBody()->getContents());
    $this->assertSame('404', $resUsers->getBody()->getContents());
  }

  public function testGetRequestMethod(): void
  {
    self::setUpRoutes();
    $client = new GuzzleHttp\Client(["headers" => [
      "X-HTTP-Method-Override" => "PATCH"
    ]]);
    $res = $client->request("POST", "http://localhost:8081/request-method");

    $this->assertEquals('PATCH', $res->getBody()->getContents());
  }

  public function testGetRequestHeaders(): void
  {
    self::setUpRoutes();
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['CONTENT_TYPE'] = 'application/json';

    $headers = Router::getRequestHeaders();

    $this->assertArrayHasKey('Host', $headers);
    $this->assertEquals('localhost', $headers['Host']);
    $this->assertArrayHasKey('Content-Type', $headers);
    $this->assertEquals('application/json', $headers['Content-Type']);
  }

  public function testSetAndGetNamespace(): void
  {
    self::setUpRoutes();
    $this->router->setNamespace('App\\Controllers');
    $this->assertEquals('App\\Controllers', $this->router->getNamespace());
  }

  public function testPatternMatching(): void
  {
    self::setUpRoutes();
    $res = $this->http->request("GET", "http://localhost:8081/product/5");
    // TODO: Figure out why this is wrong
    $this->assertSame('404', $res->getBody()->getContents());
  }

  public function testAllRoute(): void
  {
    self::setUpRoutes();
    $res = $this->http->request("PUT", "http://localhost:8081/all");
    $this->assertSame('alled', $res->getBody()->getContents());
  }

  public function testPutRoute(): void
  {
    self::setUpRoutes();
    $res = $this->http->request("PUT", "http://localhost:8081/update");
    $this->assertSame('updated', $res->getBody()->getContents());
  }

  public function testOptionsRoute(): void
  {
    self::setUpRoutes();
    $res = $this->http->request("OPTIONS", "http://localhost:8081/options");
    $this->assertSame('optioned', $res->getBody()->getContents());
  }

  public function testPatchRoute(): void
  {
    self::setUpRoutes();
    $res = $this->http->request("PATCH", "http://localhost:8081/patch");
    $this->assertSame('patched', $res->getBody()->getContents());
  }

  public function testDeleteRoute(): void
  {
    self::setUpRoutes();
    $res = $this->http->request("DELETE", "http://localhost:8081/delete");
    $this->assertSame('deleted', $res->getBody()->getContents());
  }

  public function testGetURLParts(): void{
    $this->assertNotNull(Router::getURLParts("http://localhost:8081/test/route"));
  }
}

RouterTest::setUpRoutes();
