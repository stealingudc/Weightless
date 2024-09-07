<?php

if (empty($_SESSION)) {
  session_start();
}

use Weightless\Core\Weightless;

require_once(__DIR__ . "/weightless/core/autoload.php");
require_once(__DIR__ . "/autoload.php");

Weightless::init();
