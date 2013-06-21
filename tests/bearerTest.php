<?php

require_once 'vendor/autoload.php';
try {
    $g = new \Guzzle\Http\Client();
    $f = new \fkooman\OAuth\Client\BearerRequest($g, $argv[1]);
    $response = $f->makeRequest('http://localhost/oauth/php-grades-rs/api.php/grades/');
    echo $response->getBody();
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}
