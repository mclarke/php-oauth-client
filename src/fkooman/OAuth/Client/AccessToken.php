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
    protected $token;

    /**
     * callback_id VARCHAR(255) NOT NULL,
     */
    protected $_callbackId;

    /**
     * user_id VARCHAR(255) NOT NULL,
     */
    protected $_userId;

    /**
     * issue_time INTEGER NOT NULL,
     */
    protected $_issueTime;

    /**
     * is_usable INTEGER DEFAULT 1,
     */
    protected $_isUsable;

    public function __construct($callbackId, $userId, Token $token)
    {
        $this->setToken($token);
        $this->setCallbackId($callbackId);
        $this->setUserId($userId);
        $this->setIssueTime(NULL);
        $this->setIsUsable(TRUE);
    }

    public static function fromArray(array $data)
    {
        foreach (array('callback_id', 'user_id') as $key) {
            if (!array_key_exists($key, $data)) {
                throw new AccessTokenException(sprintf("missing field '%s'", $key));
            }
        }
        $token = Token::fromArray($data);

        $t = new static($data['callback_id'], $data['user_id'], $token);
        if (array_key_exists('issue_time', $data)) {
            $t->setIssueTime($data['issue_time']);
        }
        if (array_key_exists('is_usable', $data)) {
            $t->setIsUsable($data['is_usable']);
        }

        return $t;
    }

    public function setToken(Token $token)
    {
        $this->_token = $token;
    }

    public function getToken()
    {
        return $this->_token;
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

    public function setIssueTime($issueTime)
    {
        if (NULL === $issueTime) {
            $this->_issueTime = time();
        } else {
            if (!is_numeric($issueTime) && 0 > $issueTime) {
                throw new AccessTokenException("issue_time should be positive integer");
            }
            $this->_issueTime = (int) $issueTime;
        }
    }

    public function getIssueTime()
    {
        return $this->_issueTime;
    }

    public function setIsUsable($isUsable)
    {
        $this->_isUsable = (bool) $isUsable;
    }

    public function getIsUsable()
    {
        return $this->_isUsable;
    }

}
