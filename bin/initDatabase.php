<?php

require_once 'vendor/autoload.php';

$di = new \fkooman\OAuth\Client\DiContainer();
$storage = new \fkooman\OAuth\Client\PdoStorage($di['db']);
$sql = file_get_contents('schema/db.sql');
$storage->dbQuery($sql);
