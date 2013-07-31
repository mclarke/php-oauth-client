<?php

namespace fkooman\OAuth\Client;

class Scope
{
    /** @var array */
    private $scope;

    public function __construct($scope = null)
    {
        if (null === $scope) {
            $this->scope = null;
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
            throw new ScopeException("scope cannot be empty");
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

    public function getScope()
    {
        return $this->scope;
    }

    public function getScopeAsString()
    {
        if (null === $this->scope) {
            return null;
        }

        return implode(" ", $this->scope);
    }

    public function getScopeAsNormalizedString()
    {
        if (null === $this->scope) {
            return null;
        }
        $scopeArray = $this->scope;
        sort($scopeArray, SORT_STRING);

        return implode(" ", array_values(array_unique($scopeArray, SORT_STRING)));
    }

    public function hasScope($scope)
    {
        /* if no scope is needed, all scopes suffice */
        if (null === $scope) {
            return true;
        }
        if (is_array($scope)) {
            return $this->hasScopeArray($scope);
        } elseif (is_string($scope)) {
            return $this->hasScopeString($scope);
        } else {
            throw new ScopeException("scope needs to be a non-empty string or an array");
        }
    }

    /**
     * Determines if scope in the parameter is part of this object
     */
    public function hasScopeString($scope)
    {
        if (!is_string($scope) || 0 >= strlen($scope)) {
            throw new ScopeException("scope needs to be a non-empty string");
        }

        return null !== $this->scope ? in_array($scope, $this->scope) : false;
    }

    /**
     * Determines if ALL scopes in the parameter array are part of this scope.
     *
     * If the array is empty true is returned
     *
     */
    public function hasScopeArray(array $scope)
    {
        foreach ($scope as $s) {
            if (!$this->hasScopeString($s)) {
                return false;
            }
        }

        return true;
    }
}
