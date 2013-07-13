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
    private $_p;

    private $_clientConfigId;
    private $_userId;
    private $_scope;
    private $_storage;

    public function __construct()
    {
        $this->_clientConfigId = NULL;
        $this->_userId = NULL;
        $this->_scope = NULL;
        $this->_state = NULL;
        $this->_storage = NULL;
    }

    /**
     * Set a DI container implementation
     * @param \Pimple $p
     */
    public function setDiContainer(\Pimple $p)
    {
        $this->_p = $p;
    }

    public function setClient($clientConfigId, Client $c)
    {
        $this->_clientConfigId = $clientConfigId;
        $this->_p['client'] = $c;
    }

    public function setStorage(StorageInterface $storageImpl)
    {
        $this->_storage = $storageImpl;
    }

    /**
     * Set the scope
     * @param array $scope
     */
    public function setScope(array $scope)
    {
        $this->_scope = implode(" ", array_values(array_unique($scope, SORT_STRING)));
    }

    public function setState($state)
    {
        if (!is_string($state)) {
            throw new ApiException("state should be string");
        }
        $this->_state = $state;
    }

    /**
     * Get the scope
     * @return string
     */
    public function getScope()
    {
        return $this->_scope;
    }

    /**
     * Set the user identifier
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->_userId = $userId;
    }

    /**
     * Obtain an access token from the OAuth 2.0 authorization server
     *
     * @return \fkooman\OAuth\Client\AccessToken|false
     */
    public function getAccessToken()
    {
        // do we have a valid access token?
        $accessToken = $this->_p['db']->getAccessToken($this->_clientConfigId, $this->_userId, $this->_scope);
        if (FALSE !== $accessToken) {
            if ($accessToken->getIsUsable()) {
                return $accessToken;
            }
            // no valid access token, is there a refresh_token?
            if (NULL !== $accessToken->getToken()->getRefreshToken()) {
                // obtain a new access token from refresh token
                $tokenRequest = new TokenRequest($this->_p['http'], $this->_p['client']->getTokenEndpoint(), $this->_p['client']->getClientId(), $this->_p['client']->getClientSecret());
                $newToken = $tokenRequest->withRefreshToken($accessToken->getToken()->getRefreshToken());
                if (FALSE !== $newToken) {
                    // we got a new token
                    $newAccessToken = new AccessToken($this->_clientConfigId, $this->_userId, $newToken);
                    $this->_p['db']->updateAccessToken($accessToken, $newAccessToken);

                    return $newAccessToken;
                }
            }
            // access token invalid, and not able to get a new one with a refresh token, delete it
            $this->_p['db']->deleteAccessToken($accessToken);
        }

        return FALSE;
    }

    /**
     * Invalidate the currently available access token so on the next request
     * a new token will be requested.
     */
    public function invalidateAccessToken()
    {
        $accessToken = $this->getAccessToken();
        if (FALSE !== $accessToken) {
            $this->_p['db']->invalidateAccessToken($this->getAccessToken());
        }
    }

    public function getAuthorizeUri()
    {
        // try to get a new access token
        $this->_p['db']->deleteExistingState($this->_clientConfigId, $this->_userId);
        $state = new State($this->_clientConfigId, $this->_userId, $this->_scope);
        if (NULL !== $this->_state) {
            $state->setState($this->_state);
        }
        $this->_p['db']->storeState($state);

        $q = array (
            "client_id" => $this->_p['client']->getClientId(),
            "response_type" => "code",
            "state" => $state->getState(),
        );
        if (NULL !== $this->_scope) {
            $q['scope'] = $this->_scope;
        }
        if ($this->_p['client']->getRedirectUri()) {
            $q['redirect_uri'] = $this->_p['client']->getRedirectUri();
        }

        $separator = (FALSE === strpos($this->_p['client']->getAuthorizeEndpoint(), "?")) ? "?" : "&";
        $authorizeUri = $this->_p['client']->getAuthorizeEndpoint() . $separator . http_build_query($q, NULL, '&');

        return $authorizeUri;
    }

}
