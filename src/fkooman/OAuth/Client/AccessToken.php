<?php

namespace fkooman\OAuth\Client;

class AccessToken
{
    /**
     * access_token VARCHAR(255) NOT NULL,
     */
    protected $_accessToken;

    /**
     * token_type VARCHAR(255) NOT NULL,
     */
    protected $_tokenType;

    /**
     * expires_in INTEGER DEFAULT NULL,
     */
    protected $_expiresIn;

    /**
     * refresh_token VARCHAR(255) NOT NULL,
     */
    protected $_refreshToken;

    /**
     * scope VARCHAR(255) DEFAULT NULL,
     */
    protected $_scope;

    public function __construct($accessToken = NULL, $tokenType = "bearer")
    {
        $this->setAccessToken($accessToken);
        $this->setTokenType($tokenType);
        $this->setExpiresIn(NULL);
        $this->setRefreshToken(NULL);
        $this->setScope(NULL);
    }

    public static function fromArray(array $token)
    {
        foreach (array('access_token', 'token_type') as $key) {
            if (!array_key_exists($key, $token)) {
                throw new AccessTokenException(sprintf("missing field '%s'", $key));
            }
        }
        $t = new static($token['access_token'], $token['token_type']);
        if (array_key_exists('expires_in', $token)) {
            $this->setExpiresIn($token['expires_in']);
        }
        if (array_key_exists('refresh_token', $token)) {
            $this->setRefreshToken($token['refresh_token']);
        }
        if (array_key_exists('scope', $token)) {
            $this->setScope($token['scope']);
        }

        return $t;
    }

    public function setAccessToken($accessToken)
    {
        $this->_accessToken = $accessToken;
    }

    public function getAccessToken()
    {
        return $this->_accessToken;
    }

    public function setTokenType($tokenType)
    {
        if (!in_array($tokenType, array("bearer"))) {
            throw new AccessTokenException(sprintf("unsupported token type '%s'", $tokenType));
        }
        $this->_tokenType = $tokenType;
    }

    public function getTokenType()
    {
        return $this->_tokenType;
    }

    public function setExpiresIn($expiresIn)
    {
        if (NULL !== $expiresIn) {
            if (!is_numeric($expiresIn) && 0 > $expiresIn) {
                throw new AccessTokenException("expires_in should be positive integer");
            }
            $this->_expiresIn = (int) $expiresIn;
        }
    }

    public function getExpiresIn()
    {
        return $this->_expiresIn;
    }

    public function setRefreshToken($refreshToken)
    {
        $this->_refreshToken = $refreshToken;
    }

    public function getRefreshToken()
    {
        return $this->_refreshToken;
    }

    public function setScope($scope)
    {
        if (NULL !== $scope) {
            if (!is_string($scope)) {
                throw new AccessTokenException("scope needs to be string");
            }

            $scopeTokenRegExp = '(?:\x21|[\x23-\x5B]|[\x5D-\x7E])+';
            $scopeRegExp = sprintf('/^%s(?: %s)*$/', $scopeTokenRegExp, $scopeTokenRegExp);
            $result = preg_match($scopeRegExp, $scope);
            if (1 !== $result) {
                throw new AccessTokenException(sprintf("invalid scope '%s'", $scope));
            }
            $this->_scope = self::_normalizeScope($scope);
        }
    }

    public function getScope()
    {
        return $this->_scope;
    }

    private static function _normalizeScope($scope)
    {
        $explodedScope = explode(" ", $scope);
        sort($explodedScope, SORT_STRING);

        return implode(" ", array_values(array_unique($explodedScope, SORT_STRING)));
    }

}
