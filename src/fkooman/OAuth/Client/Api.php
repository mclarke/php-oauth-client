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

/**
 * API for talking to OAuth 2.0 protected resources.
 *
 * @author François Kooman <fkooman@tuxed.net>
 */
class Api
{
    const RANDOM_LENGTH = 8;

    private $clientConfigId;
    private $clientConfig;
    private $storage;
    private $httpClient;

    private $userId;
    private $scope;
    private $state;

    public function __construct()
    {
        $this->clientConfigId = null;
        $this->clientConfig = null;
        $this->storage = null;
        $this->httpClient = null;

        $this->userId = null;
        $this->scope = null;
        $this->state = null;
    }

    public function setClientConfig($clientConfigId, ClientConfig $c)
    {
        $this->clientConfigId = $clientConfigId;
        $this->clientConfig = $c;
    }

    public function setStorage(StorageInterface $storageImpl)
    {
        $this->storage = $storageImpl;
    }

    public function setHttpClient(\Guzzle\Http\Client $client)
    {
        $this->httpClient = $client;
    }

    /**
     * Set the scope
     * @param array $scope
     */
    public function setScope(array $scope)
    {
        $this->scope = implode(" ", array_values(array_unique($scope, SORT_STRING)));
    }

    public function setState($state)
    {
        if (!is_string($state)) {
            throw new ApiException("state should be string");
        }
        $this->state = $state;
    }

    /**
     * Get the scope
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set the user identifier
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Obtain an access token from the OAuth 2.0 authorization server
     *
     * @return \fkooman\OAuth\Client\AccessToken|false
     */
    public function getAccessToken()
    {
        // do we have a valid access token?
        $accessToken = $this->storage->getAccessToken($this->clientConfigId, $this->userId, $this->scope);
        if (false !== $accessToken) {
            // check if expired
            if ($accessToken->getIssueTime() + $accessToken->getExpiresIn() < time()) {
                // expired, delete it
                $this->storage->deleteAccessToken($accessToken);

                return false;
            }

            return $accessToken;
        }

        // no valid access token, is there a refresh_token?
        $refreshToken = $this->storage->getRefreshToken($this->clientConfigId, $this->userId, $this->scope);
        if (false !== $refreshToken) {
            // obtain a new access token with refresh token
            $tokenRequest = new TokenRequest($this->httpClient, $this->clientConfig);
            $tokenResponse = $tokenRequest->withRefreshToken($refreshToken->getRefreshToken());
            if (false === $tokenResponse) {
                // unable to fetch with RefreshToken, delete it
                $this->storage->deleteRefreshToken($refreshToken);

                return false;
            }
            // we got a new token
            $scope = (null !== $tokenResponse->getScope()) ? $tokenResponse->getScope() : $this->scope;
            $accessToken = new AccessToken(array(
                "client_config_id" => $this->clientConfigId,
                "user_id" => $this->userId,
                "scope" => $scope,
                "access_token" => $tokenResponse->getAccessToken(),
                "token_type" => $tokenResponse->getTokenType(),
                "issue_time" => time(),
                "expires_in" => $tokenResponse->getExpiresIn()
            ));
            $this->storage->storeAccessToken($accessToken);
            if (null !== $tokenResponse->getRefreshToken()) {
                $refreshToken = new RefreshToken(array(
                    "client_config_id" => $this->clientConfigId,
                    "user_id" => $this->userId,
                    "scope" => $scope,
                    "refresh_token" => $tokenResponse->getRefreshTokenToken(),
                    "issue_time" => time()
                ));
                $this->storage->storeRefreshToken($refreshToken);
            }

            return $accessToken;
        }
        // no access token, and refresh token didn't work either or was not there, probably the tokens were revoked
        return false;
    }

    public function deleteAccessToken()
    {
        $accessToken = $this->getAccessToken();
        if (false !== $accessToken) {
            $this->storage->deleteAccessToken($accessToken);
        }
    }

    public function getAuthorizeUri()
    {
        // try to get a new access token
        $this->storage->deleteStateForUser($this->clientConfigId, $this->userId);
        $state = new State(array(
            "client_config_id" => $this->clientConfigId,
            "user_id" => $this->userId,
            "scope" => $this->scope,
            "issue_time" => time(),
            "state" => bin2hex(openssl_random_pseudo_bytes(self::RANDOM_LENGTH))
        ));
        if (null !== $this->state) {
            $state->setState($this->state);
        }
        if (false === $this->storage->storeState($state)) {
            throw new ApiException("unable to store state");
        }

        $q = array (
            "client_id" => $this->clientConfig->getClientId(),
            "response_type" => "code",
            "state" => $state->getState(),
        );
        if (null !== $this->scope) {
            $q['scope'] = $this->scope;
        }
        if ($this->clientConfig->getRedirectUri()) {
            $q['redirect_uri'] = $this->clientConfig->getRedirectUri();
        }

        $separator = (false === strpos($this->clientConfig->getAuthorizeEndpoint(), "?")) ? "?" : "&";
        $authorizeUri = $this->clientConfig->getAuthorizeEndpoint() . $separator . http_build_query($q, null, '&');

        return $authorizeUri;
    }
}
