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

class AccessToken extends Token
{
    /** access_token VARCHAR(255) NOT NULL */
    private $accessToken;

    /** token_type VARCHAR(255) NOT NULL */
    private $tokenType;

    /** expires_in INTEGER DEFAULT NULL */
    private $expiresIn;

    public function __construct($clientConfigId, $userId, $scope, $accessToken, $tokenType, $expiresIn = null, $issueTime = null)
    {
        parent::__construct($clientConfigId, $userId, $scope, $issueTime);
        $this->setAccessToken($accessToken);
        $this->setTokenType($tokenType);
        $this->setExpiresIn($expiresIn);
    }

    public static function fromArray(array $data)
    {
        foreach (array('client_config_id', 'user_id', 'scope', 'access_token', 'token_type') as $key) {
            if (!array_key_exists($key, $data)) {
                throw new TokenException(sprintf("missing field '%s'", $key));
            }
        }
        $t = new static($data['client_config_id'], $data['user_id'], $data['scope'], $data['access_token'], $data['token_type']);
        if (array_key_exists('expires_in', $data)) {
            $t->setExpiresIn($data['expires_in']);
        }

        return $t;
    }

    public function setAccessToken($accessToken)
    {
        if (!is_string($accessToken)) {
            throw new TokenException("access_token needs to be string");
        }
        $this->accessToken = $accessToken;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setTokenType($tokenType)
    {
        if (!is_string($tokenType)) {
            throw new TokenException("token_type needs to be string");
        }
        if (!in_array($tokenType, array("bearer"))) {
            throw new TokenException(sprintf("unsupported token type '%s'", $tokenType));
        }
        $this->tokenType = $tokenType;
    }

    public function getTokenType()
    {
        return $this->tokenType;
    }

    public function setExpiresIn($expiresIn)
    {
        if (null !== $expiresIn) {
            if (!is_numeric($expiresIn) && 0 > $expiresIn) {
                throw new TokenException("expires_in should be positive integer");
            }
            $this->expiresIn = (int) $expiresIn;
        }
    }

    public function getExpiresIn()
    {
        return $this->expiresIn;
    }
}
