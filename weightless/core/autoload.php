<?php

spl_autoload_register(function ($class) {
    // Define the base directory for the namespace prefix
    $baseDir = dirname(dirname(__DIR__)) . '/';

    // Split the class name into its components
    $parts = explode('\\', $class);
    
    // Convert all parts except the last one to lowercase
    $numParts = count($parts);
    for ($i = 0; $i < $numParts - 1; $i++) {
        $parts[$i] = strtolower($parts[$i]);
    }

    // Reassemble the path and append with .php
    $file = $baseDir . implode('/', $parts) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
