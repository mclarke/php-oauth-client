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

class Callback
{
    private $clientConfigId;
    private $clientConfig;
    private $storage;

    public function __construct()
    {

    }

    public function setClientConfig($clientConfigId, ClientConfig $clientConfig)
    {
        $this->clientConfigId = $clientConfigId;
        $this->clientConfig = $clientConfig;
    }

    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function setHttpClient(\Guzzle\Http\Client $client)
    {
        $this->httpClient = $client;
    }

    public function handleCallback(array $query)
    {
        $qState = isset($query['state']) ? $query['state'] : null;
        $qCode = isset($query['code']) ? $query['code'] : null;
        $qError = isset($query['error']) ? $query['error'] : null;
        $qErrorDescription = isset($query['error_description']) ? $query['error_description'] : null;

        if (null === $qState) {
            throw new CallbackException("state parameter missing");
        }
        $state = $this->storage->getState($this->clientConfigId, $qState);
        if (false === $state) {
            throw new CallbackException(sprintf("state '%s' for '%s' not found", $qState, $this->clientConfigId));
        }

        if (false === $this->storage->deleteState($state)) {
            throw new CallbackException("state invalid or already used");
        }

        if (null === $qCode && null === $qError) {
            throw new CallbackException("code or error parameter missing");
        }

        if (null !== $qCode) {

            $guzzle = $this->httpClient;

            $t = new TokenRequest($guzzle, $this->clientConfig);
            $tokenResponse = $t->withAuthorizationCode($qCode);
            if (false === $tokenResponse) {
                // FIXME: better error, this should probably not be 500?
                throw new CallbackException("unable to fetch token with authorization code");
            }

            // we got a new token
            $scope = (null !== $tokenResponse->getScope()) ? $tokenResponse->getScope() : $state->getScope();
            $accessToken = new AccessToken($this->clientConfigId, $state->getUserId(), $scope, time(), $tokenResponse->getAccessToken(), $tokenResponse->getTokenType(), $tokenResponse->getExpiresIn());
            $this->storage->storeAccessToken($accessToken);
            if (null !== $tokenResponse->getRefreshToken()) {
                $refreshToken = new RefreshToken($this->clientConfigId, $state->getUserId(), $scope, time(), $tokenResponse->getRefreshToken());
                $this->storage->storeRefreshToken($refreshToken);
            }

            return $accessToken;
        }

        if (null !== $qError) {
            // FIXME: how to get the error back to the API?! the API should be
            // informed as well I guess, or should we notify the user here
            // and stop, or just redirect back to the app?
            //
            // Probably store the error in the DB and let the client api
            // handle it...maybe continue without access if the app would still
            // work or try again, or whatever...
            throw new CallbackException($qError . ": " . $qErrorDescription);
        }

        // FIXME: change flow!
        // nothing left here...

    }
}
