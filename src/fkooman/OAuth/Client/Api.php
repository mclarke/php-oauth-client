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

use fkooman\Config\Config;

class Api
{
    private $_p;

    private $_callbackId;
    private $_userId;
    private $_scope;
    private $_returnUri;

    public function __construct($callbackId, $userId, $scope = array())
    {
        $this->setDiContainer(new DiContainer());

        $this->setCallbackId($callbackId);
        $this->setUserId($userId);
        $this->setScope($scope);
        $this->_returnUri = NULL;
    }

    public function setDiContainer(\Pimple $p)
    {
        $this->_p = $p;
    }

    public function setCallbackId($callbackId)
    {
        $this->_callbackId = $callbackId;

        // FIXME: also set the client... should not be here probably...
        $this->_p['client'] = Client::fromArray($this->_p['config']->s('registration')->s($callbackId)->toArray());
    }

    public function setScope(array $scope)
    {
        $this->_scope = implode(" ", array_values(array_unique($scope, SORT_STRING)));
    }

    public function getScope()
    {
        return $this->_scope;
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
        // do we have a valid access token?
        $t = $this->_p['db']->getAccessToken($this->_callbackId, $this->_userId, $this->_scope);
        // FIXME: what if there is more than one?!
        if (FALSE !== $t) {
            $token = AccessTokenContainer::fromArray($t);
            if ($token->getIsUsable()) {
                return $token->getAccessToken();
            }
            // no valid access token
            // do we have refresh_token?
            if (NULL !== $token->getRefreshToken()) {
                // obtain a new access token from refresh token
                $tokenRequest = new TokenRequest($this->_p['http'], $this->_p['client']->getTokenEndpoint(), $this->_p['client']->getClientId(), $this->_p['client']->getClientSecret());
                $newToken = $tokenRequest->fromRefreshToken($token->getRefreshToken());
                if (TRUE) {
                    // if it is ok, we update
                    $this->_p['db']->updateAccessToken($this->_callbackId, $this->_userId, $token, $newToken);
                } else {
                    // we delete
                    $this->_p['db']->deleteAccessToken($this->_callbackId, $this->_userId, $token);
                }
            }
        }

        // no valid access token and no refresh token

        // delete state if it exists, maybe there from failed attempt
        $this->_p['db']->deleteExistingState($this->_callbackId, $this->_userId);

        // store state
        $state = new State($this->_callbackId, $this->_userId, $this->_scope, $this->_returnUri);
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

        $this->_p['log']->addInfo(sprintf("redirecting browser to '%s'", $authorizeUri));

        header("HTTP/1.1 302 Found");
        header("Location: " . $authorizeUri);
        exit;
    }

    public function makeRequest($requestUri, $requestMethod = "GET", $requestHeaders = array(), $postParameters = array())
    {
        $bearerToken = $this->getAccessToken()->getAccessToken();
        $bearerRequest = new BearerRequest($this->_p['http'], $bearerToken);
        try {
            $response = $bearerRequest->makeRequest($requestUri, $requestMethod, $requestHeaders, $postParameters);

            return $response;
        } catch (BearerRequestException $e) {
            // FIXME: mark access token as invalid and fetch a new one and try again if there was
            // a refresh token
        }

    }
}
