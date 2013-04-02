<?php

require_once 'lib/SplClassLoader.php';

$c1 = new SplClassLoader("RestService", "extlib/php-rest-service/lib");
$c1->register();

$c2 =  new SplClassLoader("OAuth\\Client", "lib");
$c2->register();

use \RestService\Utils\Config as Config;
use \OAuth\Client\PdoStorage as PdoStorage;

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini");

$storage = new PdoStorage($config);
$sql = file_get_contents('schema/db.sql');
$storage->dbQuery($sql);
$storage->dbQuery("INSERT INTO schema_version (version, log) VALUES(0, 'initial schema')");
