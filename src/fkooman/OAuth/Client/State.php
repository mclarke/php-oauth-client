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

class State
{
    /**
     * state VARCHAR(255) NOT NULL,
     */
    protected $_state;

    /**
     * callback_id VARCHAR(255) NOT NULL,
     */
    protected $_callbackId;

    /**
     * user_id VARCHAR(255) NOT NULL,
     */
    protected $_userId;

    /**
     * scope VARCHAR(255) DEFAULT NULL,
     */
    protected $_scope;

    /**
     * return_uri TEXT NOT NULL,
     */
    protected $_returnUri;

    public function __construct($callbackId, $userId, $scope, $returnUri)
    {
        $this->setCallbackId($callbackId);
        $this->setUserId($userId);
        $this->setScope($scope);
        $this->setReturnUri($returnUri);
        $this->setState(NULL);
    }

    public static function fromArray(array $data)
    {
        foreach (array('callback_id', 'user_id', 'scope', 'return_uri') as $key) {
            if (!array_key_exists($key, $data)) {
                throw new StateException(sprintf("missing field '%s'", $key));
            }
        }
        $t = new static($data['callback_id'], $data['user_id'], $data['scope'], $data['return_uri']);
        if (array_key_exists('state', $data)) {
            $t->setState($data['state']);
        }

        return $t;
    }

    public function setCallbackId($callbackId)
    {
        $this->_callbackId = $callbackId;
    }

    public function getCallbackId()
    {
        return $this->_callbackId;
    }

    public function setUserId($userId)
    {
        $this->_userId = $userId;
    }

    public function getUserId()
    {
        return $this->_userId;
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

    public function setReturnUri($returnUri)
    {
        $this->_returnUri = $returnUri;
    }

    public function getReturnUri()
    {
        return $this->_returnUri;
    }

    public function setState($state)
    {
        if (NULL === $state) {
            $this->_state = bin2hex(openssl_random_pseudo_bytes(8));
        } else {
            $this->_state = $state;
        }
    }

    public function getState()
    {
        return $this->_state;
    }

}
