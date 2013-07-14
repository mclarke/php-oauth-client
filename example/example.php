<?php

require_once 'vendor/autoload.php';

$api = new \fkooman\OAuth\Client\Api();
$api->setClientConfig("foo", \fkooman\OAuth\Client\ClientConfig::fromArray(array(
    "authorize_endpoint" => "http://localhost/oauth/php-oauth/authorize.php",
    "client_id" => "foo",
    "client_secret" => "foobar",
    "token_endpoint" => "http://localhost/oauth/php-oauth/token.php",
)));
$api->setStorage(new \fkooman\OAuth\Client\SessionStorage());
//$api->setStorage(new \fkooman\OAuth\Client\PdoStorage(new \PDO($dsn, $username, $pass));
$api->setHttpClient(new \Guzzle\Http\Client());

$api->setUserId("john");
$api->setScope(array("authorizations"));
$accessToken = $api->getAccessToken();
if (false === $accessToken) {
    // no token available, we have to go to the authorization server
    $authorizeUri = $api->getAuthorizeUri();
    header("HTTP/1.1 302 Found");
    header("Location: " . $authorizeUri);
    exit;
}
$bearerToken = $accessToken->getAccessToken();
// now you can use the string $bearerToken in your HTTP request as a
// Bearer token, for example using Guzzle:
try {
    $client = new \Guzzle\Http\Client();
    $bearerAuth = new \fkooman\Guzzle\Plugin\BearerAuth\BearerAuth($bearerToken);
    $client->addSubscriber($bearerAuth);
    $response = $client->get("http://localhost/oauth/php-oauth/api.php/authorizations/")->send();
    $responseBody = $response->getBody();
    header("Content-Type: application/json");
    echo $responseBody;
} catch (\fkooman\Guzzle\Plugin\BearerAuth\Exception\BearerErrorResponseException $e) {
    // something was wrong with the access token...
    if ("invalid_token" === $e->getBearerReason()) {
        // invalid token, throw it away
        $api->deleteAccessToken();
        // now we could try again with a getAccessToken()...
        die("the access token we had appeared valid, but wasn't. We marked it as invalid. Please reload page to try again");
    } else {
        die($e->getBearerReason());
    }
} catch (\Guzzle\Http\Exception\BadResponseException $e) {
    // something was wrong with the request...
    die($e->getMessage());
}
