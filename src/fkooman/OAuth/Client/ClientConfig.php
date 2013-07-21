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

    protected $clientId;
    protected $clientSecret;
    protected $authorizeEndpoint;
    protected $tokenEndpoint;
    protected $redirectUri;
    protected $credentialsInRequestBody;
    protected $enableDebug;

    public function __construct($clientId, $clientSecret, $authorizeEndpoint, $tokenEndpoint)
    {
        $this->setClientId($clientId);
        $this->setClientSecret($clientSecret);
        $this->setAuthorizeEndpoint($authorizeEndpoint);
        $this->setTokenEndpoint($tokenEndpoint);
        $this->setRedirectUri(null);
        $this->setCredentialsInRequestBody(false);
        $this->setEnableDebug(false);
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

    /**
     * From a Google "client_secrets.json" file
     */
    public static function fromGoogleConfig(array $data)
    {
        if (!isset($data['web'])) {
            throw new ClientConfigException("no configuration 'web' found, wrong client type");
        }
        foreach (array ('client_id', 'client_secret', 'auth_uri', 'token_uri', 'redirect_uris') as $key) {
            if (!isset($data['web'][$key])) {
                throw new ClientConfigException(sprintf("missing field '%s'", $key));
            }
        }
        $c = new static($data['web']['client_id'], $data['web']['client_secret'], $data['web']['auth_uri'], $data['web']['token_uri']);

        // Google always wants credentials in request body...
        $c->setCredentialsInRequestBody(true);

        // Google always needs the redirect_uri to be specified...
        // FIXME: you can register multiple redirect_uris at Google, how to
        // choose? For now we just pick the first one...
        $c->setRedirectUri($data['web']['redirect_uris'][0]);

        return $c;
    }

    public function setClientId($clientId)
    {
        if (!is_string($clientId) || empty($clientId)) {
            throw new ClientConfigException("client_id must be non empty string");
        }
        $this->clientId = $this->validateBasicUserPass($clientId);
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function setClientSecret($clientSecret)
    {
        if (!is_string($clientSecret)) {
            // client_secret can be empty if no password is set (NOT RECOMMENDED!)
            throw new ClientConfigException("client_secret must be string");
        }
        $this->clientSecret = $this->validateBasicUserPass($clientSecret);
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function setAuthorizeEndpoint($authorizeEndpoint)
    {
        $this->authorizeEndpoint = $this->validateEndpointUri($authorizeEndpoint);
    }

    public function getAuthorizeEndpoint()
    {
        return $this->authorizeEndpoint;
    }

    public function setTokenEndpoint($tokenEndpoint)
    {
        $this->tokenEndpoint = $this->validateEndpointUri($tokenEndpoint);
    }

    public function getTokenEndpoint()
    {
        return $this->tokenEndpoint;
    }

    public function setRedirectUri($redirectUri)
    {
        if (null !== $redirectUri) {
            $this->redirectUri = $this->validateEndpointUri($redirectUri);
        }
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    public function setCredentialsInRequestBody($credentialsInRequestBody)
    {
        $this->credentialsInRequestBody = (bool) $credentialsInRequestBody;
    }

    public function getCredentialsInRequestBody()
    {
        return $this->credentialsInRequestBody;
    }

    public function setEnableDebug($enableDebug)
    {
        $this->enableDebug = (bool) $enableDebug;
    }

    public function getEnableDebug()
    {
        return $this->enableDebug;
    }

    private function validateBasicUserPass($basicUserPass)
    {
        if (1 !== preg_match(self::REGEXP_VSCHAR, $basicUserPass)) {
            throw new ClientConfigException("invalid character(s) in client_id or client_secret");
        }

        return $basicUserPass;
    }

    private function validateEndpointUri($endpointUri)
    {
        if (!is_string($endpointUri) || empty($endpointUri)) {
            throw new ClientConfigException("uri must be non empty string");
        }
        if (false === filter_var($endpointUri, FILTER_VALIDATE_URL)) {
            throw new ClientConfigException("uri must be valid URL");
        }
        // not allowed to have a fragment (#) in it
        if (null !== parse_url($endpointUri, PHP_URL_FRAGMENT)) {
            throw new ClientConfigException("uri must not contain a fragment");
        }

        return $endpointUri;
    }
}
