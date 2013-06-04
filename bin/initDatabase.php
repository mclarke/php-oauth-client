<?php

require_once __DIR__ . '/../vendor/autoload.php';

use \RestService\Utils\Config;
use \fkooman\OAuth\Client\PdoStorage;

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini");

$storage = new PdoStorage($config);
$sql = file_get_contents('schema/db.sql');
$storage->dbQuery($sql);
