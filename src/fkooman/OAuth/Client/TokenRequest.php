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
    private $_clientConfig;

    public function __construct(\Guzzle\Http\Client $c, ClientConfig $clientConfig)
    {
        $this->_c = $c;
        $this->_clientConfig = $clientConfig;
    }

    public function withAuthorizationCode($authorizationCode)
    {
        $p = array (
            "code" => $authorizationCode,
            "grant_type" => "authorization_code"
        );
        if (NULL !== $this->_clientConfig->getRedirectUri()) {
            $p['redirect_uri'] = $this->_clientConfig->getRedirectUri();
        }

        return $this->_accessTokenRequest($p);
    }

    public function withRefreshToken($refreshToken)
    {
        $p = array (
            "refresh_token" => $refreshToken,
            "grant_type" => "refresh_token"
        );

        return $this->_accessTokenRequest($p);
    }

    private function _accessTokenRequest(array $p)
    {
        if ($this->_clientConfig->getUsePostCredentials()) {
            // provide credentials in the POST body
            $p['client_id'] = $this->_clientConfig->getClientId();
            $p['client_secret'] = $this->_clientConfig->getClientSecret();
        } else {
            // use basic authentication
            $this->_c->addSubscriber(new \Guzzle\Plugin\CurlAuth\CurlAuthPlugin($this->_clientConfig->getClientId(), $this->_clientConfig->getClientSecret()));
        }

        try {
            $response = $this->_c->post($this->_clientConfig->getTokenEndpoint())->addPostFields($p)->send();
            // FIXME: what if no JSON?
            return TokenResponse::fromArray($response->json());
        } catch (\Guzzle\Http\Exception\ClientErrorResponseException $e) {
            // FIXME: if authorization code request fails? What should we do then?!
            // whenever there is 4xx error, we return FALSE, if some other error
            // occurs we just pass along the Exception...
            return FALSE;
        }
    }

}
