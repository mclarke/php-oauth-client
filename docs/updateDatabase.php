<?php
require_once 'lib/_autoload.php';

use \RestService\Utils\Config as Config;
use \OAuth\Client\PdoStorage as PdoStorage;

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini");
$storage = new PdoStorage($config);

// look for all SQL patch files and execute them in order
foreach (glob("schema/updates/*.sql") as $filename) {
    $sql = file_get_contents($filename);
    $storage->dbQuery($sql);
}
