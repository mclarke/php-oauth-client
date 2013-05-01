<?php

namespace OAuth\Client;

use \RestService\Utils\Json as Json;

class Client
{
    // VSCHAR     = %x20-7E
    const REGEXP_VSCHAR = '/^(?:[\x20-\x7E])*$/';

    private $_data;

    public function __construct($clientId, $clientSecret, $authorizeEndpoint, $tokenEndpoint)
    {
        $this->_data = array();
        $this->setClientId($clientId);
        $this->setClientSecret($clientSecret);
        $this->setAuthorizeEndpoint($authorizeEndpoint);
        $this->setTokenEndpoint($tokenEndpoint);
    }

    public static function fromArray(array $data)
    {
        foreach (array ('client_id', 'client_secret', 'authorize_endpoint', 'token_endpoint') as $key) {
            if (!isset($data[$key])) {
                throw new ClientException(sprintf("%s must be set", $key));
            }
        }

        $c = new static($data['client_id'], $data['client_secret'], $data['authorize_endpoint'], $data['token_endpoint']);

        if (isset($data['credentials_in_request_body'])) {
            $c->setCredentialsInRequestBody($data['credentials_in_request_body']);
        }

        return $c;
    }

    public static function fromJson($jsonData)
    {
        return self::fromArray(Json::dec($jsonData));
    }

    private function _validateEndpoint($r)
    {
        if (!is_string($r) || empty($r)) {
            throw new ClientException("endpoint must be non empty string");
        }
        if (FALSE === filter_var($r, FILTER_VALIDATE_URL)) {
            throw new ClientException("endpoint should be valid URL");
        }
        // not allowed to have a fragment (#) in it
        if (NULL !== parse_url($r, PHP_URL_FRAGMENT)) {
            throw new ClientException("endpoint cannot contain a fragment");
        }

        return $r;
    }

    public function setAuthorizeEndpoint($r)
    {
        $this->_data['authorize_endpoint'] = $this->_validateEndpoint($r);
    }

    public function getAuthorizeEndpoint()
    {
        return $this->_data['authorize_endpoint'];
    }

    public function setTokenEndpoint($r)
    {
        $this->_data['token_endpoint'] = $this->_validateEndpoint($r);
    }

    public function getTokenEndpoint()
    {
        return $this->_data['token_endpoint'];
    }

    private function _validateBasicUserPass($s)
    {
        if (1 !== preg_match(self::REGEXP_VSCHAR, $s)) {
            throw new ClientException("invalid character(s) in client_id or client_secret");
        }
        if (FALSE !== strpos($s, ":")) {
            throw new ClientException("invalid character in client_id or client_secret");
        }

        return $s;
    }

    public function setClientId($s)
    {
        $this->_data['client_id'] = $this->_validateBasicUserPass($s);
    }

    public function getClientId()
    {
        return isset($this->_data['client_id']) ? $this->_data['client_id'] : FALSE;
    }

    public function setClientSecret($s)
    {
        $this->_data['client_secret'] = $this->_validateBasicUserPass($s);
    }

    public function getClientSecret()
    {
        return isset($this->_data['client_secret']) ? $this->_data['client_secret'] : FALSE;
    }

    public function setCredentialsInRequestBody($c)
    {
        $this->_data['credentials_in_request_body'] = (bool) $c;
    }

    public function getCredentialsInRequestBody()
    {
        return $this->_data['credentials_in_request_body'];
    }

    public function toArray()
    {
        return $this->_data;
    }

}
