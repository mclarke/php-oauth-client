<?php

require_once 'vendor/autoload.php';

$di = new \fkooman\OAuth\Client\DiContainer();

$storage = $di['db'];
$result = $storage->getAccessToken("123","456","abc");
var_export($result);
