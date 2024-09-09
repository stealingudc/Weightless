<?php

spl_autoload_register(function ($class): void {
    // Define the base directory for the namespace prefix
    $baseDir = dirname(__DIR__, 2) . '/';

    // Split the class name into its components
    $parts = explode('\\', (string) $class);
    
    // Convert all parts except the last one to lowercase
    $numParts = count($parts);
    for ($i = 0; $i < $numParts - 1; $i++) {
        $parts[$i] = strtolower($parts[$i]);
    }

    // Reassemble the path and append with .php
    $file = $baseDir . implode('/', $parts) . '.php';

  var_dump($file);

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
