<?php

namespace Weightless\Core;

use Weightless\Core\Logic\Singleton;

/**
 * @author      Bram(us) Van Damme <bramus@bram.us>
 * @copyright   Copyright (c), 2013 Bram(us) Van Damme
 * @license     MIT public license
 */

class Router extends Singleton
{
  /**
   * @var array<string, array<mixed>> The route patterns and their handling functions
   */
  private $afterRoutes = [];

  /**
   * @var array<string, array<mixed>> The before middleware route patterns and their handling functions
   */
  private $beforeRoutes = [];

  /**
   * @var array<string|callable> The function to be executed when no route has been matched
   */
  protected $notFoundCallback = [];

  /**
   * @var string Current base route, used for (sub)route mounting
   */
  private $baseRoute = '';

  /**
   * @var string The Request Method that needs to be handled
   */
  private $requestedMethod = '';

  /**
   * @var string The Server Base Path for Router Execution
   */
  private $serverBasePath;

  /**
   * @var string Default Controllers Namespace
   */
  private $namespace = '';

  /**
   * Store a before middleware route and a handling function to be executed when accessed using one of the specified methods.
   *
   * @param array<string>          $methods Allowed methods
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function before($methods, $pattern, $fn): void
  {
    $pattern = $this->baseRoute . '/' . trim($pattern, '/');
    $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;

    foreach ($methods as $method) {
      $this->beforeRoutes[$method][] = ['pattern' => $pattern, 'fn' => $fn];
    }
  }

  /**
   * Store a route and a handling function to be executed when accessed using one of the specified methods.
   *
   * @param array<string>          $methods Allowed methods
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function match(array $methods, $pattern, $fn): void
  {
    $pattern = $this->baseRoute . '/' . trim($pattern, '/');
    $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;

    foreach ($methods as $method) {
      $this->afterRoutes[$method][] = ['pattern' => $pattern, 'fn' => $fn];
    }
  }

  /**
   * Shorthand for a route accessed using any method.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function all($pattern, $fn): void
  {
    $this->match(["GET", "POST", "PUT", "DELETE", "OPTIONS", "PATCH", "HEAD"], $pattern, $fn);
  }

  /**
   * Shorthand for a route accessed using GET.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function get($pattern, $fn): void
  {
    $this->match(['GET'], $pattern, $fn);
  }

  /**
   * Shorthand for a route accessed using POST.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function post($pattern, $fn): void
  {
    $this->match(['POST'], $pattern, $fn);
  }

  /**
   * Shorthand for a route accessed using PATCH.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function patch($pattern, $fn): void
  {
    $this->match(['PATCH'], $pattern, $fn);
  }

  /**
   * Shorthand for a route accessed using DELETE.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function delete($pattern, $fn): void
  {
    $this->match(['DELETE'], $pattern, $fn);
  }

  /**
   * Shorthand for a route accessed using PUT.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function put($pattern, $fn): void
  {
    $this->match(['PUT'], $pattern, $fn);
  }

  /**
   * Shorthand for a route accessed using OPTIONS.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function options($pattern, $fn): void
  {
    $this->match(['OPTIONS'], $pattern, $fn);
  }

  /**
   * Mounts a collection of callbacks onto a base route.
   *
   * @param string   $baseRoute The route sub pattern to mount the callbacks on
   * @param callable $fn        The callback method
   */
  public function mount($baseRoute, $fn): void
  {
    // Track current base route
    $curBaseRoute = $this->baseRoute;

    // Build new base route string
    $this->baseRoute .= $baseRoute;

    // Call the callable
    call_user_func($fn);

    // Restore original base route
    $this->baseRoute = $curBaseRoute;
  }

  /**
   * Get all request headers.
   *
   * @return array<string> The request headers
   */
  public static function getRequestHeaders(): array
  {
    $headers = [];

    // If getallheaders() is available, use that
    if (function_exists('getallheaders')) {
      $headers = getallheaders();

      // getallheaders() can return false if something went wrong
      if ($headers !== false) {
        return $headers;
      }
    }

    // Known working polyfill for older versions of PHP
    // @codeCoverageIgnoreStart

    // Method getallheaders() not available or went wrong: manually extract 'm
    foreach ($_SERVER as $name => $value) {
      if ((str_starts_with($name, 'HTTP_')) || ($name == 'CONTENT_TYPE') || ($name == 'CONTENT_LENGTH')) {
        $headers[str_replace([' ', 'Http'], ['-', 'HTTP'], ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
      }
    }

    return $headers;
    // @codeCoverageIgnoreEnd
  }

  /**
   * Get the request method used, taking overrides into account.
   *
   * @return string The Request method to handle
   */
  public static function getRequestMethod(): string
  {
    // Take the method as found in $_SERVER
    $method = @$_SERVER['REQUEST_METHOD'];

    // If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
    // @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4

    // Cannot be tested via HTTP requests.
    // @codeCoverageIgnoreStart
    if (@$_SERVER['REQUEST_METHOD'] == 'HEAD') {
      ob_start();
      $method = 'GET';
    }
    // @codeCoverageIgnoreEnd

    // Known working polyfill
    // @codeCoverageIgnoreStart

    // If it's a POST request, check for a method override header
    elseif (@$_SERVER['REQUEST_METHOD'] == 'POST') {
      $headers = self::getRequestHeaders();
      if (isset($headers['X-HTTP-Method-Override']) && in_array($headers['X-HTTP-Method-Override'], ['PUT', 'DELETE', 'PATCH'])) {
        $method = $headers['X-HTTP-Method-Override'];
      }
    }

    // @codeCoverageIgnoreEnd

    return $method ?? "GET";
  }

  /**
   * Set a Default Lookup Namespace for Callable methods.
   *
   * @param string $namespace A given namespace
   */
  public function setNamespace($namespace): void
  {
    if (is_string($namespace)) {
      $this->namespace = $namespace;
    }
  }

  /**
   * Get the given Namespace before.
   *
   * @return string The given Namespace if exists
   */
  public function getNamespace()
  {
    return $this->namespace;
  }

  /**
   * Execute the router: Loop all defined before middleware's and routes, and execute the handling function if a match was found.
   *
   * @param object|callable $callback Function to be executed after a matching route was handled (= after router middleware)
   *
   * @return bool
   */
  public function run($callback = null)
  {
    // Define which method we need to handle
    $this->requestedMethod = static::getRequestMethod();

    // Handle all before middlewares
    if (isset($this->beforeRoutes[$this->requestedMethod])) {
      // @phpstan-ignore-next-line (expects array<string, array<string, mixed>>, array given... what?)
      $this->handle($this->beforeRoutes[$this->requestedMethod]);
    }

    // Handle all routes
    $numHandled = 0;
    if (isset($this->afterRoutes[$this->requestedMethod])) {
      // @phpstan-ignore-next-line (expects array<string, array<string, mixed>>, array given... what?)
      $numHandled = $this->handle($this->afterRoutes[$this->requestedMethod], true);
    }

    // If no route was handled, trigger the 404 (if any)
    if ($numHandled === 0) {
      if (isset($this->afterRoutes[$this->requestedMethod])) {
        $this->trigger404($this->afterRoutes[$this->requestedMethod]);
      } else {
        // @codeCoverageIgnoreStart
        $this->trigger404();
        // @codeCoverageIgnoreEnd
      }
    } // If a route was handled, perform the finish callback (if any)
    // @codeCoverageIgnoreStart
    elseif ($callback && is_callable($callback)) {
      $callback();
    }

    // If it originally was a HEAD request, clean up after ourselves by emptying the output buffer
    if (@$_SERVER['REQUEST_METHOD'] == 'HEAD') {
      ob_end_clean();
    }
    // @codeCoverageIgnoreEnd

    // Return true if a route was handled, false otherwise
    return $numHandled !== 0;
  }

  /**
   * Set the 404 handling function.
   *
   * @param object|callable|string $match_fn The function to be executed
   * @param object|callable $fn The function to be executed
   */
  public function set404($match_fn, $fn = null): void
  {
    if (!is_null($fn)) {
      // @codeCoverageIgnoreStart
      // @phpstan-ignore-next-line (Such that closures can be passed as the only arg without warning)
      $this->notFoundCallback[$match_fn] = $fn;
      // @codeCoverageIgnoreEnd
    } else {
      // @phpstan-ignore-next-line (same as before)
      $this->notFoundCallback['/'] = $match_fn;
    }
  }

  /**
   * Triggers 404 response
   *
   * @param mixed $match A route pattern such as /about/system
   */
  public function trigger404(mixed $match = null): void
  {

    // Counter to keep track of the number of routes we've handled
    $numHandled = 0;

    // handle 404 pattern
    if (count($this->notFoundCallback) > 0) {
      // loop fallback-routes
      foreach ($this->notFoundCallback as $route_pattern => $route_callable) {

        // matches result
        $matches = [];

        // check if there is a match and get matches as $matches (pointer)
        $is_match = $this->patternMatches($route_pattern, $this->getCurrentUri(), $matches);

        // is fallback route match?
        if ($is_match) {

          // Rework matches to only contain the matches, not the orig string
          $matches = array_slice($matches, 1);

          // Extract the matched URL parameters (and only the parameters)
          $params = array_map(function ($match, $index) use ($matches) {


            // @codeCoverageIgnoreStart
            // We have a following parameter: take the substring from the current param position until the next one's position (thank you PREG_OFFSET_CAPTURE)
            if (isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
              if ($matches[$index + 1][0][1] > -1) {
                return trim(substr((string) $match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
              }
            } // We have no following parameters: return the whole lot

            return isset($match[0][0]) && $match[0][1] != -1 ? trim((string) $match[0][0], '/') : null;
            // @codeCoverageIgnoreEnd
          }, $matches, array_keys($matches));

          $this->invoke($route_callable);

          ++$numHandled;
        }
      }
    }
    // Not testable.
    // @codeCoverageIgnoreStart
    if (($numHandled == 0) && (isset($this->notFoundCallback['/']))) {
      $this->invoke($this->notFoundCallback['/']);
    } elseif ($numHandled == 0) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    }
    // @codeCoverageIgnoreEnd
  }

  /**
   * Replace all curly braces matches {} into word patterns (like Laravel)
   * Checks if there is a routing match
   *
   * @param $pattern
   * @param $uri
   * @param array<mixed, mixed> $matches
   *
   * @return bool -> is match yes/no
   */
  private function patternMatches(string $pattern, string $uri, array &$matches)
  {
    // Replace all curly braces matches {} into word patterns (like Laravel)
    $pattern = preg_replace('/\/{(.*?)}/', '/(.*?)', $pattern);

    // we may have a match!
    return boolval(preg_match_all('#^' . $pattern . '$#', $uri, $matches, PREG_OFFSET_CAPTURE));
  }

  /**
   * Handle a a set of routes: if a match is found, execute the relating handling function.
   *
   * @param array<string, array<string, string>> $routes       Collection of route patterns and their handling functions
   * @param bool  $quitAfterRun Does the handle function need to quit after one route was matched?
   *
   * @return int The number of routes handled
   */
  private function handle(array $routes, bool $quitAfterRun = false)
  {
    // Counter to keep track of the number of routes we've handled
    $numHandled = 0;

    $matches = [];

    // The current page URL
    $uri = $this->getCurrentUri();

    // Loop all routes
    foreach ($routes as $route) {

      // get routing matches
      $is_match = $this->patternMatches($route['pattern'], $uri, $matches);

      // is there a valid match?
      if ($is_match) {

        // Rework matches to only contain the matches, not the orig string
        $matches = array_slice($matches, 1);

        // Extract the matched URL parameters (and only the parameters)
        $params = array_map(function ($match, $index) use ($matches) {

          // We have a following parameter: take the substring from the current param position until the next one's position (thank you PREG_OFFSET_CAPTURE)
          // Not testable.
          // @codeCoverageIgnoreStart
          if (isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
            if ($matches[$index + 1][0][1] > -1) {
              return trim(substr((string) $match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
            }
          } // We have no following parameters: return the whole lot

          return isset($match[0][0]) && $match[0][1] != -1 ? trim((string) $match[0][0], '/') : null;
          // @codeCoverageIgnoreEnd
        }, $matches, array_keys($matches));

        // Call the handling function with the URL parameters if the desired input is callable
        $this->invoke($route['fn'], $params);

        ++$numHandled;

        // If we need to quit, then quit
        if ($quitAfterRun) {
          break;
        }
      }
    }

    // Return the number of routes handled
    return $numHandled;
  }

  /**
   * @param array<mixed> $params
   * */
  private function invoke(callable | string $fn, $params = []): void
  {
    if (is_callable($fn)) {
      call_user_func_array($fn, $params);
    }


    // Known working polyfill.
    // @codeCoverageIgnoreStart
    // If not, check the existence of special parameters
    elseif (stripos($fn, '@') !== false) {
      // Explode segments of given route
      [$controller, $method] = explode('@', $fn);

      // Adjust controller class if namespace has been set
      if ($this->getNamespace() !== '') {
        $controller = $this->getNamespace() . '\\' . $controller;
      }

      try {
        $reflectedMethod = new \ReflectionMethod($controller, $method);
        // Make sure it's callable
        if ($reflectedMethod->isPublic() && (!$reflectedMethod->isAbstract())) {
          $callback = [$controller, $method];
          // Make sure it's callable... again.
          if (is_callable($callback)) {
            if ($reflectedMethod->isStatic()) {
              forward_static_call_array($callback, $params);
            } else {
              // Make sure we have an instance, because a non-static method must not be called statically
              if (\is_string($controller)) {
                $controller = new $controller();
              }
              call_user_func_array($callback, $params);
            }
          }
        }
      } catch (\ReflectionException) {
        // The controller class is not available or the class does not have the method $method
      }
    }
    // @codeCoverageIgnoreEnd
  }

  /**
   * Define the current relative URI.
   *
   * @return string
   */
  public function getCurrentUri()
  {
    // Get the current Request URI and remove rewrite base path from it (= allows one to run the router in a sub folder)
    $uri = substr(rawurldecode((string) @$_SERVER['REQUEST_URI']), strlen($this->getBasePath()));

    // Don't take query params into account on the URL
    $length = strpos($uri, '?');
    if ($length != false) {
      // Not testable.
      // @codeCoverageIgnoreStart
      if (strstr($uri, '?') !== false) {
        $uri = substr($uri, 0, $length);
      }
      // @codeCoverageIgnoreEnd
    }
    // Remove trailing slash + enforce a slash at the start
    return '/' . trim($uri, '/');
  }

  /**
   * Returns the URL parts (after base path) as an array.
   *
   * @return string[]
   * 
   */
  public static function getURLParts(string $url): array
  {
    $request_url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
    if ($request_url == false) {
      // @codeCoverageIgnoreStart
      return [];
      // @codeCoverageIgnoreEnd
    }
    $request_url = rtrim($request_url, '/');
    $request_url = strtok($request_url, '?');
    $route_parts = explode('/', $url);
    $request_url_parts = explode('/', $request_url ? $request_url : "");
    array_shift($route_parts);
    array_shift($request_url_parts);
    $parameters = [];
    for ($__i__ = 0; $__i__ < count($route_parts); $__i__++) {
      $route_part = $route_parts[$__i__];
      if (preg_match("/^[$]/", $route_part)) {
        // I suck at regex.
        // @codeCoverageIgnoreStart
        $route_part = ltrim($route_part, '$');
        array_push($parameters, $request_url_parts[$__i__]);
        ${$route_part} = @$request_url_parts[$__i__];
      } else if (@$route_parts[$__i__] != @$request_url_parts[$__i__]) {
        return [];
      }
    }
    return $parameters;
    // @codeCoverageIgnoreEnd
  }

  /**
   * Return server base Path, and define it if isn't defined.
   *
   * @return string
   */
  public function getBasePath()
  {
    // Check if server base path is defined, if not define it.
    if ($this->serverBasePath === null) {
      // Known working polyfill for older versions of PHP
      // @codeCoverageIgnoreStart
      $this->serverBasePath = implode('/', array_slice(explode('/', (string) $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
      // @codeCoverageIgnoreEnd
    }

    return $this->serverBasePath;
  }

  /**
   * Explicilty sets the server base path. To be used when your entry script path differs from your entry URLs.
   * @see https://github.com/bramus/router/issues/82#issuecomment-466956078
   */
  public function setBasePath(string $serverBasePath): void
  {
    $this->serverBasePath = $serverBasePath;
  }

  /**
   * @return array<string, string>
   * */
  public static function mapToDirectory(string $path, string $fileExtension = ".wl.php"): array
  {
    // Not testable.
    // @codeCoverageIgnoreStart
    $pages = [];
    $dirit = new \DirectoryIterator($path);
    foreach ($dirit as $file) {
      if ($file->getType() !== "dir") {
        $path_parts = explode("/", $file->getPathname());
        $page_name = str_replace($fileExtension, "", $path_parts[array_key_last($path_parts)]);
        $pages[$page_name] = $file->getPathname();
      }
    }
    return $pages;
    // @codeCoverageIgnoreEnd
  }
}
