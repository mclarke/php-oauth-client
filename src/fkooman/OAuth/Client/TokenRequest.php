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
    private $c;
    private $clientConfig;

    public function __construct(\Guzzle\Http\Client $c, ClientConfigInterface $clientConfig)
    {
        $this->c = $c;
        $this->clientConfig = $clientConfig;
    }

    public function withAuthorizationCode($authorizationCode)
    {
        $p = array (
            "code" => $authorizationCode,
            "grant_type" => "authorization_code"
        );
        if (null !== $this->clientConfig->getRedirectUri()) {
            $p['redirect_uri'] = $this->clientConfig->getRedirectUri();
        }

        return $this->accessTokenRequest($p);
    }

    public function withRefreshToken($refreshToken)
    {
        $p = array (
            "refresh_token" => $refreshToken,
            "grant_type" => "refresh_token"
        );

        return $this->accessTokenRequest($p);
    }

    private function accessTokenRequest(array $p)
    {
        if ($this->clientConfig->getCredentialsInRequestBody()) {
            // provide credentials in the POST body
            $p['client_id'] = $this->clientConfig->getClientId();
            $p['client_secret'] = $this->clientConfig->getClientSecret();
        } else {
            // use basic authentication
            $curlAuth = new \Guzzle\Plugin\CurlAuth\CurlAuthPlugin($this->clientConfig->getClientId(), $this->clientConfig->getClientSecret());
            $this->c->addSubscriber($curlAuth);
        }

        try {
            $response = $this->c->post($this->clientConfig->getTokenEndpoint())->addPostFields($p)->send();
            // FIXME: what if no JSON?
            return TokenResponse::fromArray($response->json());
        } catch (\Guzzle\Http\Exception\ClientErrorResponseException $e) {
            echo $e->getMessage();
            // FIXME: if authorization code request fails? What should we do then?!
            // whenever there is 4xx error, we return FALSE, if some other error
            // occurs we just pass along the Exception...
            return false;
        }
    }
}
