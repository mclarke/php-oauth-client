<?php

use fkooman\OAuth\Client\ClientConfig;
use fkooman\OAuth\Client\Callback;
use fkooman\OAuth\Client\CallbackException;
use fkooman\OAuth\Client\SessionStorage;
use Guzzle\Http\Client;

require_once 'vendor/autoload.php';

/* OAuth client configuration */
$clientConfig = ClientConfig::fromArray(array(
    "authorize_endpoint" => "http://localhost/oauth/php-oauth/authorize.php",
    "client_id" => "foo",
    "client_secret" => "foobar",
    "token_endpoint" => "http://localhost/oauth/php-oauth/token.php",
));

try {
    /* initialize the API */
    $cb = new Callback();
    $cb->setClientConfig("foo", $clientConfig);
    $cb->setStorage(new SessionStorage());
    $cb->setHttpClient(new Client());

    /* handle the callback */
    $cb->handleCallback($_GET);

    header("HTTP/1.1 302 Found");
    header("Location: http://localhost/oauth/demo/example.php");

} catch (CallbackException $e) {
    echo sprintf("ERROR: %s", $e->getMessage());
}
