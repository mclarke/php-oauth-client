<?php

namespace fkooman\OAuth\Client;

class Context
{
    private $userId;
    private $scope;

    public function __construct($userId, array $scope)
    {
        $this->setUserId($userId);
        $this->setScope($scope);
    }

    public function setUserId($userId)
    {
        if (!is_string($userId) || 0 >= strlen($userId)) {
            throw new ContextException("userId needs to be a non-empty string");
        }
        $this->userId = $userId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setScope(array $scope)
    {
        if (0 === count($scope)) {
            throw new ContextException("need to provide at least one scope value");
        }
        foreach ($scope as $s) {
            if (!is_string($s) || 0 >= strlen($s)) {
                throw new ContextException("scope values need to be non-empty strings");
            }
        }
        // FIXME: we need to validate the scope values
        $this->scope = array_values(array_unique($scope, SORT_STRING));
    }

    public function getScope()
    {
        return implode(" ", $this->scope);
    }
}
