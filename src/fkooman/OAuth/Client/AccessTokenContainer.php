<?php

namespace fkooman\OAuth\Client;

class AccessTokenContainer
{
    protected $_accessToken;

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

    public function __construct($callbackId, $userId, AccessToken $accessToken)
    {
        $this->setAccessToken($accessToken);
        $this->setCallbackId($callbackId);
        $this->setUserId($userId);
        $this->setIssueTime(NULL);
        $this->setIsUsable(TRUE);
    }

    public static function fromArray(array $data)
    {
        foreach (array('callback_id', 'user_id') as $key) {
            if (!array_key_exists($key, $data)) {
                throw new AccessTokenContainerException(sprintf("missing field '%s'", $key));
            }
        }
        $accessToken = AccessToken::fromArray($data);

        $t = new static($data['callback_id'], $data['user_id'], $accessToken);
        if (array_key_exists('issue_time', $data)) {
            $t->setIssueTime($data['issue_time']);
        }
        if (array_key_exists('is_usable', $data)) {
            $t->setIsUsable($data['is_usable']);
        }

        return $t;
    }

    public function setAccessToken(AccessToken $accessToken)
    {
        $this->_accessToken = $accessToken;
    }

    public function getAccessToken()
    {
        return $this->_accessToken;
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
