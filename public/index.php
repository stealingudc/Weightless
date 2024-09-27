<?php

/*
 * The Weightless Framework.
 * @author Vladimir Damian <vladimirdamian.dev@gmail.com>
 * @license GPL License
 * */

// You should probably not remove any of the following.

if (empty($_SESSION)) {
  session_start();
}

require_once(dirname(__DIR__) . "/vendor/autoload.php");

use Weightless\Core\Weightless;

Weightless::init();
