<?php

require_once 'vendor/autoload.php';

$token = new \fkooman\OAuth\Client\Token("foobar", "bearer");

echo $token->getAccessToken() . PHP_EOL;
echo $token->getTokenType() . PHP_EOL;

$accessToken = new \fkooman\OAuth\Client\AccessToken("conext", "fkooman", $token);

echo $accessToken->getUserId() . PHP_EOL;

echo $accessToken->getToken()->getAccessToken() . PHP_EOL;

$data = array ("callback_id" => "callback_id", "user_id" => "user_id", "access_token" => "access_token", "token_type" => "bearer");

$atc = \fkooman\OAuth\Client\AccessToken::fromArray($data);

echo $atc->getToken()->getAccessToken() . PHP_EOL;
echo $atc->getToken()->getTokenType() . PHP_EOL;
