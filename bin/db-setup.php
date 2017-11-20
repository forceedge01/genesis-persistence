<?php

require __DIR__ . '/vendor/autoload.php';
$configFilePath = __DIR__ . '/../../db-config.php';

if (! file_exists($configFilePath)) {
    throw new Exception('Config file with db details must be defined here ' . $configFilePath);
}

$dbconfig = require $configFilePath;

if (! isset($argv[1])) {
    throw new Exception('Path to model directory must be provided as first parameter.');
}

$modelDirectory = $argv[1];

use Genesis\Services\Persistence;

$mapper = new Persistence\MapperService(new Persistence\DatabaseService($dbconfig));

$files = scandir($modelDirectory);
$exclude = ['.', '..'];

function info($msg)
{
    echo PHP_EOL . $msg . PHP_EOL;
}

foreach ($files as $file) {
    if (! in_array($file, $exclude)) {
        $file = str_replace('.php', '', $file);
        $class = "App\\Ebay\\Representations\\$file";

        // Check if the representation needs to be saved in the database.
        if (defined("$class::MAPPED") and $class::MAPPED === false) {
            continue;
        }

        info("Creating table for: '$class'");
        $mapper->createTable($class);
    }
}
