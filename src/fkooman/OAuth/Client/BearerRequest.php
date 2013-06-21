<?php

namespace fkooman\OAuth\Client;

class BearerRequest
{
    private $_guzzle;
    private $_bearerToken;

    public function __construct(\Guzzle\Http\Client $guzzle, $bearerToken)
    {
        $this->_guzzle = $guzzle;
        $this->_bearerToken = $bearerToken;
    }

    public function makeRequest($requestUri, $requestMethod = "GET", $requestHeaders = array(), $postParameters = array())
    {
        $request = $this->_guzzle->createRequest($requestMethod, $requestUri);
        foreach ($requestHeaders as $k => $v) {
            $request->setHeader($k, $v);
        }

        $request->setHeader("Authorization", "Bearer " . $this->_bearerToken);

        if ("POST" === $requestMethod) {
            $request->addPostFields($postParameters);
        }

        try {
            $response = $request->send();

            return $response;
        } catch (ClientErrorResponseException $e) {
            if (401 === $e->getResponse()->getStatusCode()) {
                // FIXME: check whether error WWW-Authenticate type is "invalid_token",
                // only then it makes sense to try again...
                throw new BearerRequestException("invalid bearer token");
            }
            throw $e;
        }
    }
}
