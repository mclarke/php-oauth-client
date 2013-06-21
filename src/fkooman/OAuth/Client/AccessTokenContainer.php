<?php

namespace fkooman\OAuth\Client;

class AccessTokenContainer extends AccessToken
{
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

    public function __construct($callbackId, $userId, $accessToken = NULL, $tokenType = "bearer")
    {
        parent::__construct($accessToken, $tokenType);
        $this->setCallbackId($callbackId);
        $this->setUserId($userId);
        $this->setIssueTime(NULL);
        $this->setIsUsable(TRUE);
    }

    public static function fromArray(array $token)
    {
        foreach (array('callback_id', 'user_id', 'access_token', 'token_type') as $key) {
            if (!array_key_exists($key, $token)) {
                throw new AccessTokenContainerException(sprintf("missing field '%s'", $key));
            }
        }
        $t = new static($token['callback_id'], $token['user_id'], $token['access_token'], $token['token_type']);
        if (array_key_exists('issue_time', $token)) {
            $this->setIssueTime($token['issue_time']);
        }
        if (array_key_exists('is_usable', $token)) {
            $this->setIsUsable($token['is_usable']);
        }

        // call setter methods from parent...
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
                throw new AccessTokenContainerException("issue_time should be positive integer");
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
