<?php

require __DIR__ . '/../autoload.php';

use Genesis\Services\Persistence;

$configFilePath = __DIR__ . '/../../db-config.php';

if (! file_exists($configFilePath)) {
    throw new Exception('Config file with db details must be defined here ' . $configFilePath);
}

$dbconfig = require $configFilePath;

if (! isset($argv[1])) {
    throw new Exception('Path to model directory must be provided as first parameter.');
}

$modelDirectory = $argv[1];

$mapper = new Persistence\MapperService(new Persistence\DatabaseService($dbconfig));

$files = scandir($modelDirectory);
$exclude = ['.', '..'];

function info($msg)
{
    echo PHP_EOL . $msg . PHP_EOL;
}

function getTableColumns($table, $mapper)
{
    $properties = $mapper->getDatabaseService()->execute("PRAGMA table_info('$table')");
    $formattedProperties = [];

    foreach ($properties as $property) {
        $formattedProperties[] = $property['name'];
    }

    // Re-add the id to the end of the list, less conflict for all tables during migration.
    $key = array_search('id', $formattedProperties);
    unset($formattedProperties[$key]);
    $formattedProperties[] = 'id';

    // Reset array keys.
    return array_values($formattedProperties);
}

info('Execute for migration:');
$dropBackup = in_array('drop-backup', $argv);
$createBackup = in_array('create-backup', $argv);
$verbose = in_array('-v', $argv);
foreach ($files as $file) {
    if (! in_array($file, $exclude)) {
        $file = str_replace('.php', '', $file);
        $class = "App\\Ebay\\Representations\\$file";

        // Check if the representation needs to be saved in the database.
        if (defined("$class::MAPPED") and $class::MAPPED === false) {
            continue;
        }

        $table = $mapper->getTableFromClass($class);
        $helperTable = $table . 'Backup';

        if ($dropBackup) {
            if ($verbose) {
                info("Dropping helper table {$helperTable}");
            }
            $query = "DROP TABLE IF EXISTS {$helperTable};";
            $mapper->getDatabaseService()->execute($query);
        }

        if ($createBackup) {
            if ($verbose) {
                info("Backup/rename table {$table} to {$helperTable}");
            }

            $query = "ALTER TABLE {$table} RENAME TO {$helperTable}";
            $mapper->getDatabaseService()->execute($query);
        }

        if ($verbose) {
            info("Create table for class {$class}");
        }
        ob_start();
        $mapper->createTable($class);
        $output = ob_get_clean();
        if ($verbose) {
            info($output);
        }

        $properties = $mapper->getPropertiesWithTypesFromClass($class);
        $newTableColumns = getTableColumns($table, $mapper);
        $migrationTableColumns = getTableColumns($helperTable, $mapper);

        // Compare migration tables.
        $diff = array_diff_assoc($newTableColumns, $migrationTableColumns);

        if ($diff) {
            info('Following differences found in new table vs migration table');
            print_r($diff);
        }

        $newTableColumns = '`' . implode($newTableColumns, '`, `') . '`';
        $migrationTableColumns = '`' . implode($migrationTableColumns, '`, `') . '`';

        $query = "INSERT INTO {$table} ($newTableColumns) SELECT $migrationTableColumns FROM {$helperTable}";

        info("$query");
    }
}
