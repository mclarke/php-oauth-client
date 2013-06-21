<?php

/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
        return Token::fromArray($response->json());
    }

}
