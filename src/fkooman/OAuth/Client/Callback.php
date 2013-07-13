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

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Request;

class Callback
{
    private $clientConfigId;
    private $clientConfig;
    private $storage;

    public function __construct() {}

    public function setClientConfig($clientConfigId, ClientConfig $clientConfig)
    {
        $this->clientConfigId = $clientConfigId;
        $this->clientConfig = $clientConfig;
    }

    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function handleCallback(Request $r)
    {
        $qState = $r->get('state');
        $qCode = $r->get('code');
        $qError = $r->get('error');

        if (NULL === $qState) {
            throw new BadRequestHttpException("state parameter missing");
        }
        $state = $this->storage->getState($clientConfigId, $qState);
        if (FALSE === $state) {
            throw new BadRequestHttpException("state not found");
        }

        if (FALSE === $this->storage->deleteState($state)) {
            throw new BadRequestHttpException("state invalid or already used");
        }

        if (NULL === $qCode && NULL === $qError) {
            throw new BadRequestHttpException("code or error parameter missing");
        }

        if (NULL !== $qCode) {

            $guzzle = $this->_p['http'];

            $t = new TokenRequest($guzzle, $this->clientConfig->getTokenEndpoint(), $this->clientConfig->getClientId(), $this->clientConfig->getClientSecret());
            $t->setRedirectUri($this->clientConfig->getRedirectUri());
            $token = $t->withAuthorizationCode($qCode);
            if (false === $token) {
                // FIXME: better error, this should probably not be 500?
                throw new CallbackException("unable to fetch token with authorization code");
            }
            if (NULL === $token->getScope()) {
                $token->setScope($state->getScope());
            }
            $accessToken = new AccessToken($this->clientConfigId, $state->getUserId(), $token);
            $this->storage->storeAccessToken($accessToken);

            return $state->getReturnUri();
        }

        if (NULL !== $qError) {
            // FIXME: how to get the error back to the API?! the API should be
            // informed as well I guess, or should we notify the user here
            // and stop, or just redirect back to the app?
            //
            // Probably store the error in the DB and let the client api
            // handle it...maybe continue without access if the app would still
            // work or try again, or whatever...
            throw new CallbackException($qError . ": " . $r->get('error_description'));
        }

        // nothing left here...

    }

}
