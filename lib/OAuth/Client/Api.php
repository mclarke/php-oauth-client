<?php

namespace OAuth\Client;

use \RestService\Utils\Config as Config;
use \RestService\Utils\Logger as Logger;
use \RestService\Http\HttpResponse as HttpResponse;
use \RestService\Http\HttpRequest as HttpRequest;
use \RestService\Http\OutgoingHttpRequest as OutgoingHttpRequest;

class Api
{
    private $_callbackId;

    private $_userId;
    private $_scope;
    private $_returnUri;

    private $_c;
    private $_logger;
    private $_storage;

    public function __construct($callbackId)
    {
        $this->_callbackId = $callbackId;

        $this->_userId = NULL;
        $this->_scope = NULL;
        $this->_returnUri = NULL;

        $this->_c = new Config(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini");
        $this->_logger = new Logger($this->_c->getSectionValue('Log', 'logLevel'), $this->_c->getValue('serviceName'), $this->_c->getSectionValue('Log', 'logFile'), $this->_c->getSectionValue('Log', 'logMail', FALSE));

        $this->_storage = new PdoStorage($this->_c);
    }

    public function setScope($scope)
    {
        $this->_scope = $scope;
    }

    public function setUserId($userId)
    {
        $this->_userId = $userId;
    }

    public function setReturnUri($returnUri)
    {
        $this->_returnUri = $returnUri;
    }

    public function getAccessToken()
    {
        // FIXME: deal with user giving less scope than requested
        // FIXME: rename this class to something nice
        // FIXME: do something with ApiException, rename it at least...

        // check if application is registered
        $result = $this->_storage->getApplication($this->_callbackId);
        if (FALSE === $result) {
            throw new ApiException("invalid callback id");
        }
        $client = json_decode($result['client_data'], TRUE);

        // check if access token is actually available for this user, if
        $token = $this->_storage->getAccessToken($this->_callbackId, $this->_userId, $this->_scope);

        if (!empty($token)) {
            if (NULL === $token['expires_in']) {
                // no known expires_in, so assume token is valid
                return $token['access_token'];
            }
            if ($token['issue_time'] + $token['expires_in'] > time()) {
                // appears valid
                return $token['access_token'];
            }
            $this->_storage->deleteAccessToken($this->_callbackId, $this->_userId, $token['access_token']);
        }

        // do we have a refreshToken?
        $token = $this->_storage->getRefreshToken($this->_callbackId, $this->_userId, $this->_scope);
        if (!empty($token)) {
            // there is something here...
            // exchange it for an access_token
            // FIXME: do somthing with these ugly exceptions
            try {
                $p = array (
                    "refresh_token" => $token['refresh_token'],
                    "grant_type" => "refresh_token"
                );

                $h = new HttpRequest($client['token_endpoint'], "POST");
                $h->setHeader("Authorization", "Basic " . base64_encode($client['client_id'] . ':' . $client['client_secret']));
                $h->setPostParameters($p);

                $this->_logger->logDebug($h);

                $response = OutgoingHttpRequest::makeRequest($h);

                $this->_logger->logDebug($response);

                if (200 !== $response->getStatusCode()) {
                    throw new ApiException("unable to retrieve access token using refresh token");
                }

                $data = json_decode($response->getContent(), TRUE);
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
                    $this->_storage->storeRefreshToken($this->_callbackId, $this->_userId, $scope, $data['refresh_token']);
                }

                return $data['access_token'];

            } catch (ApiException $e) {
                // remove the refresh_token, it didn't work so get rid of it, it might not be the fault of the refresh_token, but anyway...
                $this->_storage->deleteRefreshToken($this->_callbackId, $this->_userId, $token['refresh_token']);

                $this->_logger->logWarn("unable to fetch access token using refresh token, falling back to getting a new authorization code...");
                // do nothing...
            }
        }

        // if there is no access_token and refresh_token failed, just ask for
        // authorization again

        // no access token obtained so far...

        // FIXME: delete existing state thingies?

        // store state
        $state = bin2hex(openssl_random_pseudo_bytes(8));
        try {
            $this->_storage->storeState($this->_callbackId, $this->_userId, $state, $this->_returnUri);
        } catch (StorageException $e) {
            echo $e->getMessage() . $e->getDescription();
            die();
        }
        $q = array (
            "client_id" => $client['client_id'],
            "response_type" => "code",
            "state" => $state,
        );
        if (NULL !== $this->_scope) {
            $q['scope'] = $this->_scope;
        }
        if (array_key_exists('redirect_uri', $client) && !empty($client['redirect_uri'])) {
            $q['redirect_uri'] = $client['redirect_uri'];
        }
        $separator = (FALSE === strpos($client['authorize_endpoint'], "?")) ? "?" : "&";
        $authorizeUri = $client['authorize_endpoint'] . $separator . http_build_query($q);
        $httpResponse = new HttpResponse(302);
        $httpResponse->setHeader("Location", $authorizeUri);
        $httpResponse->sendResponse();
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

            $this->_logger->logDebug($request);
            $response = OutgoingHttpRequest::makeRequest($request);
            $this->_logger->logDebug($response);

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
