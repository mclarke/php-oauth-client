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

use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Log\PsrLogAdapter;
use Guzzle\Plugin\Log\LogPlugin;
use Guzzle\Log\MessageFormatter;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Symfony\Component\HttpFoundation\Request;

use fkooman\Config\Config;

class Api
{
    private $_p;

    private $_callbackId;
    private $_userId;
    private $_requestScope;
    private $_returnUri;

    public function __construct($callbackId, $userId, $requestScope = array())
    {
        $this->setDiContainer(new DiContainer());

        $this->setCallbackId($callbackId);
        $this->setUserId($userId);
        $this->setRequestScope($requestScope);
        $this->_returnUri = NULL;
    }

    public function setDiContainer(\Pimple $p)
    {
        $this->_p = $p;
    }

    public function setCallbackId($callbackId)
    {
        $this->_p['client'] = Client::fromArray($this->_p['config']->s('registration')->s($callbackId)->toArray());
    }

    public function setScope(array $scope)
    {
        $this->_scope = implode(" ", array_values(array_unique($scope, SORT_STRING)));
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
        $t = $this->_p['storage']->getAccessToken($this->_callbackId, $this->_userId, $this->_scope);
        // FIXME: what if there is more than one?!
        if (FALSE !== $t) {
            $token = new AccessToken($t);
            if ($token->isAccessTokenUsable()) {
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
                    $this->_p['storage']->updateAccessToken($this->_callbackId, $this->_userId, $token, $newToken);
                } else {
                    // we delete
                    $this->_p['storage']->deleteAccessToken($this->_callbackId, $this->_userId, $token);
                }
            }
        }

        // no valid access token and no refresh token

        // delete state if it exists, maybe there from failed attempt
        $this->_storage->deleteStateIfExists($this->_callbackId, $this->_userId);

        // store state
        $state = bin2hex(openssl_random_pseudo_bytes(8));
        $this->_p['storage']->storeState($this->_callbackId, $this->_userId, $this->_scope, $this->_returnUri, $state);

        $q = array (
            "client_id" => $this->_p['client']->getClientId(),
            "response_type" => "code",
            "state" => $state,
        );
        if (NULL !== $this->_data['scope']) {
            $q['scope'] = $this->_data['scope'];
        }
        if ($this->_p['client']->getRedirectUri()) {
            $q['redirect_uri'] = $this->_p['client']->getRedirectUri();
        }

        $separator = (FALSE === strpos($this->_p['client']->getAuthorizeEndpoint(), "?")) ? "?" : "&";
        $authorizeUri = $this->_p['client']->getAuthorizeEndpoint() . $separator . http_build_query($q, NULL, '&');

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
