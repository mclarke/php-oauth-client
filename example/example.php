<?php

use fkooman\OAuth\Client\Api;
use fkooman\OAuth\Client\ClientConfig;
use fkooman\OAuth\Client\SessionStorage;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;
use fkooman\Guzzle\Plugin\BearerAuth\BearerAuth;
use fkooman\Guzzle\Plugin\BearerAuth\Exception\BearerErrorResponseException;

require_once 'vendor/autoload.php';

/* OAuth client configuration */
$clientConfig = ClientConfig::fromArray(array(
    "authorize_endpoint" => "http://localhost/oauth/php-oauth/authorize.php",
    "client_id" => "foo",
    "client_secret" => "foobar",
    "token_endpoint" => "http://localhost/oauth/php-oauth/token.php",
));

/* the OAuth 2.0 protected URI */
$apiUri = "http://localhost/oauth/php-oauth/api.php/authorizations/";

/* initialize the API */
$api = new Api();
$api->setClientConfig("foo", $clientConfig);
$api->setStorage(new SessionStorage());
$api->setHttpClient(new Client());

/* the user to bind the tokens to */
$api->setUserId("john");

/* the scope you want to request */
$api->setScope(array("authorizations"));

/* check if an access token is available */
$accessToken = $api->getAccessToken();
if (false === $accessToken) {
    /* no valid access token available, go to authorization server */
    header("HTTP/1.1 302 Found");
    header("Location: " . $api->getAuthorizeUri());
    exit;
}

/* we have an access token that appears valid */
try {
    $client = new Client();
    $bearerAuth = new BearerAuth($accessToken);
    $client->addSubscriber($bearerAuth);
    $response = $client->get($apiUri)->send();
    header("Content-Type: application/json");
    echo $response->getBody();
} catch (BearerErrorResponseException $e) {
    if ("invalid_token" === $e->getBearerReason()) {
        // the token we used was invalid, possibly revoked, we throw it away
        $api->deleteAccessToken();
    }
    echo sprintf('ERROR: %s (%s)', $e->getBearerReason() , $e->getMessage());
} catch (\Guzzle\Http\Exception\BadResponseException $e) {
    // something was wrong with the request, server did not accept it
    echo sprintf('ERROR: %s', $e->getMessage());
}
