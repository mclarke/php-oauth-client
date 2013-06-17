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

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Log\PsrLogAdapter;
use Guzzle\Plugin\Log\LogPlugin;
use Guzzle\Log\MessageFormatter;
use Guzzle\Plugin\CurlAuth\CurlAuthPlugin;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Symfony\Component\HttpFoundation\Request;

use fkooman\Config\Config;

class Api
{
    private $_data;
    private $_c;
    private $_logger;
    private $_storage;

    public function __construct($callbackId)
    {
        $this->_data = array();
        $this->_data['callback_id'] = $callbackId;
        $this->_data['user_id'] = NULL;
        $this->_data['scope'] = NULL;

        $configFile = dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.yaml";
        $this->_c = Config::fromYamlFile($configFile);

        $this->_data['return_uri'] = Request::createFromGlobals()->getUri();

        $this->_logger = new Logger($this->_c->getValue('name', FALSE, 'php-oauth-client'));
        $this->_logger->pushHandler(new StreamHandler($this->_c->getSection('log')->getValue('file', false, NULL), $this->_c->getSection('log')->getValue('level', false, 400)));

        $this->_storage = new PdoStorage($this->_c->getSection('storage'));
    }

    public function setScope(array $scope)
    {
        foreach ($scope as $s) {
            $scopePattern = '/^(?:\x21|[\x23-\x5B]|[\x5D-\x7E])+$/';
            $result = preg_match($scopePattern, $s);
            if (1 !== $result) {
                $msg = sprintf("invalid scope '%s'", $s);
                $this->_logger->addError($msg, $this->_data);
                throw new ApiException($msg);
            }
        }
        sort($scope, SORT_STRING);
        $this->_data['scope'] = implode(" ", array_values(array_unique($scope, SORT_STRING)));
        $this->_logger->addInfo("set scope", $this->_data);
    }

    public function setUserId($userId)
    {
        $this->_data['user_id'] = $userId;
        $this->_logger->addInfo("set user_id", $this->_data);
    }

    public function setReturnUri($returnUri)
    {
        $this->_data['return_uri'] = $returnUri;
        $this->_logger->addInfo("set return_uri", $this->_data);
    }

    public function getAccessToken()
    {
        $this->_logger->addInfo("get access_token", $this->_data);

        // FIXME: deal with user giving less scope than requested
        // FIXME: rename this class to something nice
        // FIXME: do something with ApiException, rename it at least...

        // check if application is registered
        $client = Client::fromArray($this->_c->getSection('registration')->getSection($this->_data['callback_id'])->toArray());

        // check if access token is actually available for this user, if
        $token = $this->_storage->getAccessToken($this->_data['callback_id'], $this->_data['user_id'], $this->_data['scope']);

        if (!empty($token)) {
            if (NULL === $token['expires_in']) {
                // no known expires_in, so assume token is valid
                $this->_logger->addInfo("token found, no known expiry", $this->_data);

                return $token['access_token'];
            }
            if ($token['issue_time'] + $token['expires_in'] > time()) {
                // appears valid
                $this->_logger->addInfo("token found, not expired yet", $this->_data);

                return $token['access_token'];
            }
            $this->_logger->addInfo("token found, but expired", $this->_data);
            $this->_storage->deleteAccessToken($this->_data['callback_id'], $this->_data['user_id'], $token['access_token']);
        }

        $this->_logger->addInfo("no token, request a new one", $this->_data);
        // do we have a refreshToken?
        $token = $this->_storage->getRefreshToken($this->_data['callback_id'], $this->_data['user_id'], $this->_data['scope']);
        if (!empty($token)) {
            $this->_logger->addInfo("refresh token found, use it to request a new token", $this->_data);
            // there is something here...
            // exchange it for an access_token
            // FIXME: do somthing with these ugly exceptions
            try {
                $p = array (
                    "refresh_token" => $token['refresh_token'],
                    "grant_type" => "refresh_token"
                );

                $c = new GuzzleClient();

                $logPlugin = new LogPlugin(new PsrLogAdapter($this->_logger), MessageFormatter::DEFAULT_FORMAT);
                $c->addSubscriber($logPlugin);

                if ($client->getCredentialsInRequestBody()) {
                    $p['client_id'] = $client->getClientId();
                    $p['client_secret'] = $client->getClientSecret();
                } else {
                    // FIXME: you have to be careful to not use ':' in the client_id/client_secret
                    // use basic authentication
                    $c->addSubscriber(new CurlAuthPlugin($client->getClientId(), $client->getClientSecret()));
                }
                $response = $c->post($client->getTokenEndpoint())->addPostFields($p)->send();
                $data = $response->json();
                if (!is_array($data)) {
                    throw new ApiException("unable to decode access token response");
                }

                $requiredKeys = array('token_type', 'access_token');
                foreach ($requiredKeys as $key) {
                    if (!array_key_exists($key, $data)) {
                        throw new ApiException("missing key in access_token response");
                    }
                }
                $expiresIn = array_key_exists("expires_in", $data) ? $data['expires_in'] : NULL;
                $scope = array_key_exists("scope", $data) ? $data['scope'] : $state['scope'];

                $this->_storage->storeAccessToken($this->_data['callback_id'], $this->_data['user_id'], $scope, $data['access_token'], time(), $expiresIn);

                // did we get a new refresh_token?
                if (array_key_exists("refresh_token", $data)) {
                    // we got a refresh_token, store this as well
                    // FIXME: maybe the delete the one we have now?
                    $this->_storage->storeRefreshToken($this->_data['callback_id'], $this->_data['user_id'], $scope, $data['refresh_token']);
                }

                return $data['access_token'];

            } catch (ClientErrorResponseException $e) {
                $this->_logger->addInfo("unable to use refresh_token", $this->_data);
                $this->_storage->deleteRefreshToken($this->_data['callback_id'], $this->_data['user_id'], $token['refresh_token']);
            } catch (ApiException $e) {
                // remove the refresh_token, it didn't work so get rid of it, it might not be the fault of the refresh_token, but anyway...
                // FIXME: this should only be for broken server responses, not for wrong refresh token or something
                $this->_logger->addInfo("unable to use refresh_token (ApiException)", $this->_data);

                $this->_storage->deleteRefreshToken($this->_data['callback_id'], $this->_data['user_id'], $token['refresh_token']);

                //$this->_logger->logWarn("unable to fetch access token using refresh token, falling back to getting a new authorization code...");
                // do nothing...
            }
        }

        $this->_logger->addInfo("no token, no refresh token, request a new access token", $this->_data);
        // if there is no access_token and refresh_token failed, just ask for
        // authorization again

        // no access token obtained so far...

        // delete state if it exists
        $this->_storage->deleteStateIfExists($this->_data['callback_id'], $this->_data['user_id']);

        // store state
        $state = bin2hex(openssl_random_pseudo_bytes(8));

        $this->_storage->storeState($this->_data['callback_id'], $this->_data['user_id'], $this->_data['scope'], $this->_data['return_uri'], $state);

        $q = array (
            "client_id" => $client->getClientId(),
            "response_type" => "code",
            "state" => $state,
        );
        if (NULL !== $this->_data['scope']) {
            $q['scope'] = $this->_data['scope'];
        }
        if ($client->getRedirectUri()) {
            $q['redirect_uri'] = $client->getRedirectUri();
        }

        $separator = (FALSE === strpos($client->getAuthorizeEndpoint(), "?")) ? "?" : "&";
        $authorizeUri = $client->getAuthorizeEndpoint() . $separator . http_build_query($q);

        $this->_logger->addInfo(sprintf("redirecting browser to '%s'", $authorizeUri), $this->_data);

        header("HTTP/1.1 302 Found");
        header("Location: " . $authorizeUri);
        exit;
    }

    public function makeRequest($requestUri, $requestMethod = "GET", $requestHeaders = array(), $postParameters = array())
    {
        // we try to get the data from the RS, if that fails (with invalid_token)
        // we try to obtain another access token after deleting the old one and
        // try the request to the RS again. If that fails (again) an exception
        // is thrown and the client application has to deal with it, but that
        // would imply a serious problem somewhere...

        // FIXME: does this actually work?
        // we lose count if we redirect to the AS. So only with refresh_token
        // problems this counting is of any use... need more thinking...
        for ($i = 0; $i < 2; $i++) {
            $accessToken = $this->getAccessToken();

            $c = new GuzzleClient();
            $logPlugin = new LogPlugin(new PsrLogAdapter($this->_logger), MessageFormatter::DEFAULT_FORMAT);
            $c->addSubscriber($logPlugin);

            $request = $c->createRequest($requestMethod, $requestUri);

            foreach ($requestHeaders as $k => $v) {
                $request->setHeader($k, $v);
            }

            // if Authorization header already exists, it is overwritten here...
            $request->setHeader("Authorization", "Bearer " . $accessToken);

            if ("POST" === $requestMethod) {
                $request->addPostFields($postParameters);
            }

            try {
                $response = $request->send();

                return $response;
            } catch (ClientErrorResponseException $e) {
                if (401 === $e->getResponse()->getStatusCode()) {
                // FIXME: check whether error WWW-Authenticate type is "invalid_token", only then it makes sense to try again
                    $this->_storage->deleteAccessToken($this->_data['callback_id'], $this->_data['user_id'], $accessToken);
                    continue;
                } else {
                    // throw it again
                    throw $e;
                }
            }

            return $response;
        }
        throw new ApiException("unable to obtain access token that was acceptable by the RS, wrong RS?");
    }

}
