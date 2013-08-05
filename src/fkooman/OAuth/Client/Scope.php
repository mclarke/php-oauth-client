<?php

namespace fkooman\OAuth\Client;

class Scope
{
    /** @var array */
    private $scope;

    public function __construct($scope = null)
    {
        if (null === $scope) {
            $this->scope = array();
        } elseif (is_array($scope)) {
            $this->scope = $this->scopeFromArray($scope);
        } elseif (is_string($scope)) {
            $this->scope = $this->scopeFromString($scope);
        } else {
            throw new ScopeException("scope needs to be a string, array or null");
        }
    }

    private function scopeFromArray(array $scope)
    {
        if (0 === count($scope)) {
            return array();
        }
        foreach ($scope as $s) {
            if (!$this->validateScopeToken($s)) {
                throw new ScopeException(sprintf("invalid scope token '%s'", $s));
            }
        }

        return $scope;
    }

    private function scopeFromString($scope)
    {
        $scopeArray = explode(" ", $scope);

        return $this->scopeFromArray($scopeArray);
    }

    private function validateScopeToken($scopeToken)
    {
        if (!is_string($scopeToken) || 0 >= strlen($scopeToken)) {
            throw new ScopeException("scope token must be a non-empty string");
        }
        $scopeTokenRegExp = '/^(?:\x21|[\x23-\x5B]|[\x5D-\x7E])+$/';
        $result = preg_match($scopeTokenRegExp, $scopeToken);

        return 1 === $result;
    }

    public function getScopeAsArray()
    {
        return $this->scope;
    }

    public function getScopeAsString()
    {
        return implode(" ", $this->scope);
    }

    public function __toString()
    {
        return $this->getScopeAsString();
    }

    /**
     * Determine if the scope specified in the parameter is part of this object
     */
    public function hasScope(Scope $scope)
    {
        foreach ($scope->getScopeAsArray() as $s) {
            if (!in_array($s, $this->scope)) {
                return false;
            }
        }

        return true;
    }

}
