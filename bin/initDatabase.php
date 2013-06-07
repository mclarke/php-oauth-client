<?php

require_once __DIR__ . '/../vendor/autoload.php';

use fkooman\Config\Config;
use fkooman\OAuth\Client\PdoStorage;

$config = Config::fromYamlFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.yaml");

$storage = new PdoStorage($config->getSection("storage"));
$sql = file_get_contents('schema/db.sql');
$storage->dbQuery($sql);
