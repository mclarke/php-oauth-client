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

if ($argc !== 2) {
        die("ERROR: please specify file with client registration information" . PHP_EOL);
}

$jsonData = file_get_contents($argv[1]);
if (FALSE === $jsonData) {
        die("ERROR: unable to read file" . PHP_EOL);
}

$data = json_decode($jsonData, TRUE);
if (NULL === $data || !is_array($data)) {
    die("ERROR: data is not JSON or wrong format" . PHP_EOL);
}

foreach ($data as $key => $value) {
    if (FALSE === $storage->getApplication($key)) {
        // does not exist yet, add
        echo "Adding '" . $key . "'..." . PHP_EOL;
        $storage->storeApplication($key, json_encode($value));
    }
}
