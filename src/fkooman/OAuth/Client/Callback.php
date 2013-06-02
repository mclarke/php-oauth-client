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
use RestService\Http\HttpRequest;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use RestService\Utils\Config;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Log\PsrLogAdapter;
use Guzzle\Plugin\Log\LogPlugin;
use Guzzle\Log\MessageFormatter;
use Guzzle\Plugin\CurlAuth\CurlAuthPlugin;

class Callback
{

    private $_c;
    private $_l;

    private $_clientConfigFile;

    private $_storage;
    private $_logger;

    public function __construct(Config $c)
    {
        $this->_c = $c;

        $this->_clientConfigFile = dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "clientConfig.json";

        $this->_logger = new Logger($this->_c->getValue('serviceName'));
        $this->_logger->pushHandler(new StreamHandler($this->_c->getSectionValue('Log', 'logFile'), $this->_c->getSectionValue('Log', 'logLevel')));

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
        $client = Client::fromConfig($this->_clientConfigFile, $callbackId);

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
            if ($client->getRedirectUri()) {
                $p['redirect_uri'] = $client->getRedirectUri();
            }

            $c = new GuzzleClient();

            $logPlugin = new LogPlugin(new PsrLogAdapter($this->_logger), MessageFormatter::DEBUG_FORMAT);
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

            header("HTTP/1.1 302 Found");
            header("Location: " . $state['return_uri']);
            // FIXME: should return a response instead of NULL?!
            // will integrate with REST framework I guess...
            exit;
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
