<?php
require_once 'lib/_autoload.php';

use \RestService\Utils\Config as Config;
use \OAuth\Client\PdoStorage as PdoStorage;

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini");
$storage = new PdoStorage($config);

$v = $storage->getDatabaseVersion();
$dbVersion = $v['version'];

echo "[INFO] current database version: " . $dbVersion . PHP_EOL;

// look for all SQL patch files and execute them in order
foreach (glob("schema/updates/*.sql") as $filename) {
    list($version, $log) = explode("_", basename($filename, ".sql"), 2);
    if ($version > $dbVersion) {
        echo "[UPDATE] applying " . $filename . "..." . PHP_EOL;
        $sql = file_get_contents($filename);
        $storage->dbQuery($sql);
        $storage->updateDatabaseVersion($version, $log);
    }
}
