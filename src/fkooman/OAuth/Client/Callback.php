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

use fkooman\Json\Json;
use Guzzle\Log\PsrLogAdapter;
use Guzzle\Plugin\Log\LogPlugin;
use Guzzle\Log\MessageFormatter;
use Guzzle\Plugin\CurlAuth\CurlAuthPlugin;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Request;

class Callback
{
    private $_p;

    public function __construct(\Pimple $p)
    {
        $this->_p = $p;
    }

    public function handleCallback(Request $r)
    {
        $callbackId = $r->get('id');
        if (NULL === $callbackId) {
            throw new BadRequestHttpException("callback identifier parameter missing");
        }

        // check if application is registered
        $client = Client::fromArray($this->_p['config']->s('registration')->s($callbackId)->toArray());

        $qState = $r->get('state');
        $qCode = $r->get('code');
        $qError = $r->get('error');

        if (NULL === $qState) {
            throw new BadRequestHttpException("state parameter missing");
        }
        $state = $this->_p['storage']->getState($callbackId, $qState);
        if (FALSE === $state) {
            throw new BadRequestHttpException("state not found");
        }

        if (FALSE === $this->_p['storage']->deleteState($callbackId, $qState)) {
            throw new BadRequestHttpException("state invalid or already used");
        }

        if (NULL === $qCode && NULL === $qError) {
            throw new CallbackException("code or error parameter missing");
        }

        if (NULL !== $qCode) {
            $p = array (
                "code" => $qCode,
                "grant_type" => "authorization_code"
            );
            if ($client->getRedirectUri()) {
                $p['redirect_uri'] = $client->getRedirectUri();
            }

            $c = new \Guzzle\Http\Client();

            $logPlugin = new LogPlugin(new PsrLogAdapter($this->_p['log']), MessageFormatter::DEBUG_FORMAT);
            $c->addSubscriber($logPlugin);

            if ($client->getCredentialsInRequestBody()) {
                $p['client_id'] = $client->getClientId();
                $p['client_secret'] = $client->getClientSecret();
            } else {
                // use basic authentication
                $c->addSubscriber(new CurlAuthPlugin($client->getClientId(), $client->getClientSecret()));
            }
            $response = $c->post($client->getTokenEndpoint())->addPostFields($p)->send();
            $data = $response->json();
            if (!is_array($data)) {
                throw new \Exception("unable to decode access token response");
            }

            $requiredKeys = array('token_type', 'access_token');
            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $data)) {
                    throw new \Exception("missing key in access_token response");
                }
            }
            $expiresIn = array_key_exists("expires_in", $data) ? $data['expires_in'] : NULL;
            $scope = array_key_exists("scope", $data) ? $data['scope'] : $state['scope'];

            $this->_p['storage']->storeAccessToken($callbackId, $state['user_id'], $scope, $data['access_token'], time(), $expiresIn);

            if (array_key_exists("refresh_token", $data)) {
                // we got a refresh_token, store this as well
                $this->_p['storage']->storeRefreshToken($callbackId, $state['user_id'], $scope, $data['refresh_token']);
            }

            return $state['return_uri'];
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
