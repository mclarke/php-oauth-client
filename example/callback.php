<?php

require_once 'vendor/autoload.php';

$cb = new \fkooman\OAuth\Client\Callback();
$cb->setClientConfig("foo", \fkooman\OAuth\Client\ClientConfig::fromArray(array(
    "authorize_endpoint" => "http://localhost/oauth/php-oauth/authorize.php",
    "client_id" => "foo",
    "client_secret" => "foobar",
    "token_endpoint" => "http://localhost/oauth/php-oauth/token.php",
)));
$cb->setStorage(new \fkooman\OAuth\Client\SessionStorage());
$cb->setHttpClient(new \Guzzle\Http\Client());
$cb->handleCallback($_GET);

header("Location: /oauth/demo/example.php");
