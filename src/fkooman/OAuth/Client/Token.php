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

class Token
{
    /** client_config_id VARCHAR(255) NOT NULL */
    private $clientConfigId;

    /** user_id VARCHAR(255) NOT NULL */
    private $userId;

    /** scope VARCHAR(255) NOT NULL */
    private $scope;

    /** issue_time INTEGER NOT NULL */
    private $issueTime;

    public function __construct($clientConfigId, $userId, $scope, $issueTime = null)
    {
        $this->setClientConfigId($clientConfigId);
        $this->setUserId($userId);
        $this->setScope($scope);
        $this->setIssueTime($issueTime);
    }

    public static function fromArray(array $data)
    {
        foreach (array('client_config_id', 'user_id', 'scope') as $key) {
            if (!array_key_exists($key, $data)) {
                throw new TokenException(sprintf("missing field '%s'", $key));
            }
        }
        $t = new static($data['client_config_id'], $data['user_id'], $data['scope']);
        if (array_key_exists('issue_time', $data)) {
            $t->setIssueTime($data['issue_time']);
        }

        return $t;
    }

    public function setClientConfigId($clientConfigId)
    {
        $this->clientConfigId = $clientConfigId;
    }

    public function getClientConfigId()
    {
        return $this->clientConfigId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setScope($scope)
    {
        if (!is_string($scope)) {
            throw new TokenException("scope needs to be string");
        }
        $scopeTokenRegExp = '(?:\x21|[\x23-\x5B]|[\x5D-\x7E])+';
        $scopeRegExp = sprintf('/^%s(?: %s)*$/', $scopeTokenRegExp, $scopeTokenRegExp);
        $result = preg_match($scopeRegExp, $scope);
        if (1 !== $result) {
            throw new TokenException(sprintf("invalid scope '%s'", $scope));
        }
        $this->scope = self::normalizeScope($scope);
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function setIssueTime($issueTime)
    {
        if (null === $issueTime) {
            $this->issueTime = time();
        } else {
            if (!is_numeric($issueTime) && 0 > $issueTime) {
                throw new TokenException("issue_time should be positive integer");
            }
            $this->issueTime = (int) $issueTime;
        }
    }

    public function getIssueTime()
    {
        return $this->issueTime;
    }

    private static function normalizeScope($scope)
    {
        $explodedScope = explode(" ", $scope);
        sort($explodedScope, SORT_STRING);

        return implode(" ", array_values(array_unique($explodedScope, SORT_STRING)));
    }
}
