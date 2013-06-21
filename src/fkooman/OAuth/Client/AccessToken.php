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

    public static function fromArray(array $data)
    {
        foreach (array('access_token', 'token_type') as $key) {
            if (!array_key_exists($key, $data)) {
                throw new AccessTokenException(sprintf("missing field '%s'", $key));
            }
        }
        $t = new static($data['access_token'], $data['token_type']);
        if (array_key_exists('expires_in', $data)) {
            $t->setExpiresIn($data['expires_in']);
        }
        if (array_key_exists('refresh_token', $data)) {
            $t->setRefreshToken($data['refresh_token']);
        }
        if (array_key_exists('scope', $data)) {
            $t->setScope($data['scope']);
        }

        return $t;
    }

    public function setAccessToken($accessToken)
    {
        if (!is_string($accessToken)) {
            throw new AccessTokenException("access_token needs to be string");
        }
        $this->_accessToken = $accessToken;
    }

    public function getAccessToken()
    {
        return $this->_accessToken;
    }

    public function setTokenType($tokenType)
    {
        if (!is_string($tokenType)) {
            throw new AccessTokenException("token_type needs to be string");
        }
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
        if (NULL !== $refreshToken) {
            if (!is_string($refreshToken)) {
                throw new AccessTokenException("refresh_token needs to be string");
            }
            $this->_refreshToken = $refreshToken;
        }
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

    public function __toString()
    {
        $output = "AccessToken" . PHP_EOL;
        $output .= "\taccess_token: " . $this->getAccessToken() . PHP_EOL;
        $output .= "\ttoken_type: " . $this->getTokenType() . PHP_EOL;
        $output .= PHP_EOL;

        return $output;
    }

}
