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

class ClientConfig
{
    // VSCHAR     = %x20-7E
    const REGEXP_VSCHAR = '/^(?:[\x20-\x7E])*$/';

    protected $_clientId;
    protected $_clientSecret;
    protected $_authorizeEndpoint;
    protected $_tokenEndpoint;
    protected $_redirectUri;
    protected $_credentialsInRequestBody;
    protected $_enableDebug;

    public function __construct($clientId, $clientSecret, $authorizeEndpoint, $tokenEndpoint)
    {
        $this->setClientId($clientId);
        $this->setClientSecret($clientSecret);
        $this->setAuthorizeEndpoint($authorizeEndpoint);
        $this->setTokenEndpoint($tokenEndpoint);
        $this->setRedirectUri(NULL);
        $this->setCredentialsInRequestBody(FALSE);
        $this->setEnableDebug(FALSE);
    }

    public static function fromArray(array $data)
    {
        foreach (array ('client_id', 'client_secret', 'authorize_endpoint', 'token_endpoint') as $key) {
            if (!isset($data[$key])) {
                throw new ClientConfigException(sprintf("missing field '%s'", $key));
            }
        }

        $c = new static($data['client_id'], $data['client_secret'], $data['authorize_endpoint'], $data['token_endpoint']);
        if (isset($data['redirect_uri'])) {
            $c->setRedirectUri($data['redirect_uri']);
        }
        if (isset($data['credentials_in_request_body'])) {
            $c->setCredentialsInRequestBody($data['credentials_in_request_body']);
        }
        if (isset($data['enable_debug'])) {
            $c->setEnableDebug($data['enable_debug']);
        }

        return $c;
    }

    public function setClientId($clientId)
    {
        if (!is_string($clientId) || empty($clientId)) {
            throw new ClientConfigException("client_id must be non empty string");
        }
        $this->_clientId = $this->_validateBasicUserPass($clientId);
    }

    public function getClientId()
    {
        return $this->_clientId;
    }

    public function setClientSecret($clientSecret)
    {
        if (!is_string($clientSecret)) {
            // client_secret can be empty if no password is set (NOT RECOMMENDED!)
            throw new ClientConfigException("client_secret must be string");
        }
        $this->_clientSecret = $this->_validateBasicUserPass($clientSecret);
    }

    public function getClientSecret()
    {
        return $this->_clientSecret;
    }

    public function setAuthorizeEndpoint($authorizeEndpoint)
    {
        $this->_authorizeEndpoint = $this->_validateEndpointUri($authorizeEndpoint);
    }

    public function getAuthorizeEndpoint()
    {
        return $this->_authorizeEndpoint;
    }

    public function setTokenEndpoint($tokenEndpoint)
    {
        $this->_tokenEndpoint = $this->_validateEndpointUri($tokenEndpoint);
    }

    public function getTokenEndpoint()
    {
        return $this->_tokenEndpoint;
    }

    public function setRedirectUri($redirectUri)
    {
        if (NULL !== $redirectUri) {
            $this->_redirectUri = $this->_validateEndpointUri($redirectUri);
        }
    }

    public function getRedirectUri()
    {
        return $this->_redirectUri;
    }

    public function setCredentialsInRequestBody($credentialsInRequestBody)
    {
        $this->_credentialsInRequestBody = (bool) $credentialsInRequestBody;
    }

    public function getCredentialsInRequestBody()
    {
        return $this->_credentialsInRequestBody;
    }

    public function setEnableDebug($enableDebug)
    {
        $this->_enableDebug = (bool) $enableDebug;
    }

    public function getEnableDebug()
    {
        return $this->_enableDebug;
    }

    private function _validateBasicUserPass($basicUserPass)
    {
        if (1 !== preg_match(self::REGEXP_VSCHAR, $basicUserPass)) {
            throw new ClientConfigException("invalid character(s) in client_id or client_secret");
        }

        return $basicUserPass;
    }

    private function _validateEndpointUri($endpointUri)
    {
        if (!is_string($endpointUri) || empty($endpointUri)) {
            throw new ClientConfigException("uri must be non empty string");
        }
        if (FALSE === filter_var($endpointUri, FILTER_VALIDATE_URL)) {
            throw new ClientConfigException("uri must be valid URL");
        }
        // not allowed to have a fragment (#) in it
        if (NULL !== parse_url($endpointUri, PHP_URL_FRAGMENT)) {
            throw new ClientConfigException("uri must not contain a fragment");
        }

        return $endpointUri;
    }

}
