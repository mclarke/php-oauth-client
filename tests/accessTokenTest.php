<?php

require_once 'vendor/autoload.php';

$accessToken = new \fkooman\OAuth\Client\AccessToken("foobar", "bearer");

echo $accessToken->getAccessToken() . PHP_EOL;
echo $accessToken->getTokenType() . PHP_EOL;

$accessTokenContainer = new \fkooman\OAuth\Client\AccessTokenContainer("conext", "fkooman", $accessToken);

echo $accessTokenContainer->getUserId() . PHP_EOL;

echo $accessTokenContainer->getAccessToken()->getAccessToken() . PHP_EOL;

$data = array ("callback_id" => "callback_id", "user_id" => "user_id", "access_token" => "access_token", "token_type" => "bearer");

$atc = \fkooman\OAuth\Client\AccessTokenContainer::fromArray($data);

echo $atc->getAccessToken()->getAccessToken() . PHP_EOL;
echo $atc->getAccessToken()->getTokenType() . PHP_EOL;
