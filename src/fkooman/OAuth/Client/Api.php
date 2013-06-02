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

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \RestService\Utils\Config;
#use \fkooman\Json\Json;

use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Log\PsrLogAdapter;
use Guzzle\Plugin\Log\LogPlugin;
use Guzzle\Log\MessageFormatter;
use Guzzle\Plugin\CurlAuth\CurlAuthPlugin;
use Guzzle\Http\Exception\ClientErrorResponseException;

use \RestService\Http\HttpRequest;
use \RestService\Http\IncomingHttpRequest;
use \RestService\Http\OutgoingHttpRequest;

class Api
{
    private $_callbackId;

    private $_clientConfigFile;

    private $_userId;
    private $_scope;
    private $_returnUri;

    private $_c;
    private $_logger;
    private $_storage;

    public function __construct($callbackId)
    {
        $this->_callbackId = $callbackId;
        $this->_clientConfigFile = dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "clientConfig.json";

        $this->_userId = NULL;
        $this->_scope = NULL;

        // determine the URL from which this script was called...
        $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());
        $this->_returnUri = $request->getRequestUri()->getUri();

        $this->_c = new Config(dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini");

        $this->_logger = new Logger($this->_c->getValue('serviceName'));
        $this->_logger->pushHandler(new StreamHandler($this->_c->getSectionValue('Log', 'logFile'), $this->_c->getSectionValue('Log', 'logLevel')));

        $this->_storage = new PdoStorage($this->_c);
    }

    public function setScope(array $scope)
    {
        foreach ($scope as $s) {
            $scopePattern = '/^(?:\x21|[\x23-\x5B]|[\x5D-\x7E])+$/';
            $result = preg_match($scopePattern, $s);
            if (1 !== $result) {
                $msg = sprintf("set invalid scope value '%s' for config '%s'", $s, $this->_callbackId);
                $this->_logger->addError($msg);
                throw new ApiException($msg);
            }
        }
        sort($scope, SORT_STRING);
        $this->_scope = implode(" ", array_values(array_unique($scope, SORT_STRING)));
        $this->_logger->addInfo(sprintf("scope set to '%s' for config '%s'", $this->_scope, $this->_callbackId));
    }

    public function setUserId($userId)
    {
        $this->_userId = $userId;
        $this->_logger->addInfo(sprintf("userId set to '%s' for config '%s'", $this->_userId, $this->_callbackId));
    }

    public function setReturnUri($returnUri)
    {
        $this->_returnUri = $returnUri;
        $this->_logger->addInfo(sprintf("returnUri set to '%s' for config '%s'", $this->_returnUri, $this->_callbackId));
    }

    public function getAccessToken()
    {
        // FIXME: deal with user giving less scope than requested
        // FIXME: rename this class to something nice
        // FIXME: do something with ApiException, rename it at least...

        // check if application is registered
        $client = Client::fromConfig($this->_clientConfigFile, $this->_callbackId);

        // check if access token is actually available for this user, if
        $token = $this->_storage->getAccessToken($this->_callbackId, $this->_userId, $this->_scope);

        if (!empty($token)) {
            if (NULL === $token['expires_in']) {
                // no known expires_in, so assume token is valid
                $this->_logger->addInfo("token found, no known expiry");

                return $token['access_token'];
            }
            if ($token['issue_time'] + $token['expires_in'] > time()) {
                // appears valid
                $this->_logger->addInfo("token found, not expired yet");

                return $token['access_token'];
            }
            $this->_logger->addInfo("token found, but expired");
            $this->_storage->deleteAccessToken($this->_callbackId, $this->_userId, $token['access_token']);
        }

        $this->_logger->addInfo("no token, request a new one");
        // do we have a refreshToken?
        $token = $this->_storage->getRefreshToken($this->_callbackId, $this->_userId, $this->_scope);
        if (!empty($token)) {
            $this->_logger->addInfo("refresh token found, use it to request a new token");
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

                $this->_storage->storeAccessToken($this->_callbackId, $this->_userId, $scope, $data['access_token'], time(), $expiresIn);

                // did we get a new refresh_token?
                if (array_key_exists("refresh_token", $data)) {
                    // we got a refresh_token, store this as well
                    // FIXME: maybe the delete the one we have now?
                    $this->_storage->storeRefreshToken($this->_callbackId, $this->_userId, $scope, $data['refresh_token']);
                }

                return $data['access_token'];

            } catch (ClientErrorResponseException $e) {
                $this->_logger->addError($e->getRequest() . $e->getResponse());
                $this->_storage->deleteRefreshToken($this->_callbackId, $this->_userId, $token['refresh_token']);
            } catch (ApiException $e) {
                // remove the refresh_token, it didn't work so get rid of it, it might not be the fault of the refresh_token, but anyway...
                // FIXME: this should only be for broken server responses, not for wrong refresh token or something

                $this->_storage->deleteRefreshToken($this->_callbackId, $this->_userId, $token['refresh_token']);

                //$this->_logger->logWarn("unable to fetch access token using refresh token, falling back to getting a new authorization code...");
                // do nothing...
            }
        }

        $this->_logger->addInfo("no token, no refresh token, request a new access token");
        // if there is no access_token and refresh_token failed, just ask for
        // authorization again

        // no access token obtained so far...

        // delete state if it exists
        $this->_storage->deleteStateIfExists($this->_callbackId, $this->_userId);

        // store state
        $state = bin2hex(openssl_random_pseudo_bytes(8));

        $this->_storage->storeState($this->_callbackId, $this->_userId, $this->_scope, $this->_returnUri, $state);

        $q = array (
            "client_id" => $client->getClientId(),
            "response_type" => "code",
            "state" => $state,
        );
        if (NULL !== $this->_scope) {
            $q['scope'] = $this->_scope;
        }
        if ($client->getRedirectUri()) {
            $q['redirect_uri'] = $client->getRedirectUri();
        }

        $separator = (FALSE === strpos($client->getAuthorizeEndpoint(), "?")) ? "?" : "&";
        $authorizeUri = $client->getAuthorizeEndpoint() . $separator . http_build_query($q);

        $this->_logger->addInfo(sprintf("redirecting browser to '%s'", $authorizeUri));

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

            $request = new HttpRequest($requestUri, $requestMethod);
            $request->setHeaders($requestHeaders);
            // if Authorization header already exists, it is overwritten here...
            $request->setHeader("Authorization", "Bearer " . $accessToken);

            if ("POST" === $requestMethod) {
                $request->setPostParameters($postParameters);
            }

            //$this->_logger->logDebug($request);
            $response = OutgoingHttpRequest::makeRequest($request);
            //$this->_logger->logDebug($response);

            if (401 === $response->getStatusCode()) {
                // FIXME: check whether error WWW-Authenticate type is "invalid_token", only then it makes sense to try again
                $this->_storage->deleteAccessToken($this->_callbackId, $this->_userId, $accessToken);
                continue;
            }

            return $response;
        }
        throw new ApiException("unable to obtain access token that was acceptable by the RS, wrong RS?");
    }

}
