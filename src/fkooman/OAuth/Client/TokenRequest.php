<?php

namespace fkooman\OAuth\Client;

class TokenRequest
{
    private $_c;

    private $_tokenEndpoint;
    private $_clientId;
    private $_clientSecret;
    private $_redirectUri;
    private $_usePostCredentials;

    public function __construct(\Guzzle\Http\Client $c, $tokenEndpoint, $clientId, $clientSecret)
    {
        $this->_c = $c;
        $this->setTokenEndpoint($tokenEndpoint);
        $this->setClientId($clientId);
        $this->setClientSecret($clientSecret);
        $this->_redirectUri = NULL;
        $this->usePostCredentials(FALSE);
    }

    public function setClientId($clientId)
    {
        // FIXME: validate
        // FIXME: warn if it contains a ':'
        $this->_clientId = $clientId;
    }

    public function setClientSecret($clientSecret)
    {
        // FIXME: validate
        // FIXME: warn if it contains a ':'
        $this->_clientSecret = $clientSecret;
    }

    public function setRedirectUri($redirectUri)
    {
        // FIXME: validate URL
        $this->_redirectUri = $redirectUri;
    }

    public function usePostCredentials($boolean)
    {
        $this->_usePostCredentials = (bool) $boolean;
    }

    public function setTokenEndpoint($tokenEndpoint)
    {
        // FIXME: validate URL
        $this->_tokenEndpoint = $tokenEndpoint;
    }

    public function withAuthorizationCode($authorizationCode)
    {
        $p = array (
            "code" => $authorizationCode,
            "grant_type" => "authorization_code"
        );
        if (NULL !== $this->_redirectUri) {
            $p['redirect_uri'] = $this->_redirectUri;
        }

        return $this->_accessTokenRequest($this->_tokenEndpoint, $p);
    }

    public function withRefreshToken($refreshToken)
    {
        $p = array (
            "refresh_token" => $refreshToken,
            "grant_type" => "refresh_token"
        );

        return $this->_accessTokenRequest($this->_tokenEndpoint, $p);
    }

    private function _accessTokenRequest($tokenEndpoint, array $p)
    {
        if ($this->_usePostCredentials) {
            // provide credentials in the POST body
            $p['client_id'] = $this->_clientId;
            $p['client_secret'] = $this->_clientSecret;
        } else {
            // use basic authentication
            $this->_c->addSubscriber(new \Guzzle\Plugin\CurlAuth\CurlAuthPlugin($this->_clientId, $this->_clientSecret));
        }

        $response = $this->_c->post($tokenEndpoint)->addPostFields($p)->send();
        // FIXME: what if no JSON?
        return AccessToken::fromArray($response->json());
    }

}
