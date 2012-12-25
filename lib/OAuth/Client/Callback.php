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

namespace OAuth\Client;

use \RestService\Utils\Config as Config;
use \RestService\Utils\Logger as Logger;
use \RestService\Http\HttpRequest as HttpRequest;
use \RestService\Http\HttpResponse as HttpResponse;
use \RestService\Http\OutgoingHttpRequest as OutgoingHttpRequest;

class Callback
{

    private $_c;
    private $_l;
    private $_storage;

    public function __construct(Config $c, Logger $l)
    {
        $this->_c = $c;
        $this->_l = $l;

        $this->_storage = new PdoStorage($c);
    }

    //public function handleRequest($callbackId, HttpRequest $r)
    public function handleRequest(HttpRequest $r)
    {
        $callbackId = $r->getQueryParameter("id");
        if (NULL === $callbackId) {
            throw new CallbackException("no callback id specified");
        }

        // check if application is registered
        $result = $this->_storage->getApplication($callbackId);
        if (FALSE === $result) {
            throw new CallbackException("invalid callback id");
        }
        $client = json_decode($result['client_data'], TRUE);

        $qState = $r->getQueryParameter("state");
        $qCode = $r->getQueryParameter("code");
        $qError = $r->getQueryParameter("error");

        if (NULL === $qState) {
            throw new CallbackException("invalid state (missing)");
        }
        $state = $this->_storage->getState($callbackId, $qState);
        if (FALSE === $state) {
            throw new CallbackException("invalid state (not found)");
        }

        if (FALSE === $this->_storage->deleteState($callbackId, $qState)) {
            throw new CallbackException("invalid state");
        }

        if (NULL === $qCode && NULL === $qError) {
            throw new CallbackException("required parameter missing, either code or error must be a query parameter");
        }

        if (NULL !== $qCode) {
            $p = array (
                "code" => $qCode,
                "grant_type" => "authorization_code"
            );
            if (array_key_exists('redirect_uri', $client) && !empty($client['redirect_uri'])) {
                $p['redirect_uri'] = $client['redirect_uri'];
            }
            $h = new HttpRequest($client['token_endpoint'], "POST");
            $h->setHeader("Authorization", "Basic " . base64_encode($client['client_id'] . ':' . $client['client_secret']));
            $h->setPostParameters($p);

            $this->_l->logDebug($h);

            $response = OutgoingHttpRequest::makeRequest($h);

            $this->_l->logDebug($response);

            if (200 !== $response->getStatusCode()) {
                throw new CallbackException("unable to retrieve access token using authorization code");
            }

            $data = json_decode($response->getContent(), TRUE);
            if (!is_array($data)) {
                throw new CallbackException("unable to decode access token response");
            }

            $requiredKeys = array('token_type', 'access_token');
            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $data)) {
                    throw new CallbackException("missing key in access_token response");
                }
            }
            $expiresIn = array_key_exists("expires_in", $data) ? $data['expires_in'] : NULL;
            $scope = array_key_exists("scope", $data) ? $data['scope'] : $state['scope'];

            $this->_storage->storeAccessToken($callbackId, $state['user_id'], $scope, $data['access_token'], time(), $expiresIn);

            if (array_key_exists("refresh_token", $data)) {
                // we got a refresh_token, store this as well
                $this->_storage->storeRefreshToken($callbackId, $state['user_id'], $scope, $data['refresh_token']);
            }

            $httpResponse = new HttpResponse(302);
            $httpResponse->setHeader("Location", $state['return_uri']);

            return $httpResponse;
        }

        if (NULL !== $qError) {
            // FIXME: how to get the error back to the API?! the API should be
            // informed as well I guess, or should we notify the user here
            // and stop, or just redirect back to the app?
            //
            // Probably store the error in the DB and let the client api
            // handle it...maybe continue without access if the app would still
            // work or try again, or whatever...
            throw new CallbackException($qError . ": " . $r->getQueryParameter("error_description"));
        }

        // nothing left here...

    }

}
