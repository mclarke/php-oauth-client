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

class RefreshToken extends Token
{
    /** refresh_token VARCHAR(255) NOT NULL */
    private $refreshToken;

    public function __construct($clientConfigId, $userId, $scope, $refreshToken, $issueTime = null)
    {
        parent::__construct($clientConfigId, $userId, $scope, $issueTime);
        $this->setRefreshToken($refreshToken);
    }

    public static function fromArray(array $data)
    {
        foreach (array('client_config_id', 'user_id', 'scope', 'refresh_token') as $key) {
            if (!array_key_exists($key, $data)) {
                throw new TokenException(sprintf("missing field '%s'", $key));
            }
        }
        $t = new static($data['client_config_id'], $data['user_id'], $data['scope'], $data['refresh_token']);

        return $t;
    }

    public function setRefreshToken($refreshToken)
    {
        if (!is_string($refreshToken)) {
            throw new TokenException("refresh_token needs to be string");
        }
        $this->refreshToken = $refreshToken;
    }

    public function getRefreshToken()
    {
        return $this->refreshToken;
    }
}
