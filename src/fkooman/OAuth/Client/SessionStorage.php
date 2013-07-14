<?php

namespace fkooman\OAuth\Client;

class SessionStorage implements StorageInterface
{
    public function __construct()
    {
        session_start();
    }

    public function getAccessToken($clientConfigId, $userId, $scope)
    {
        if (!array_key_exists("access_token", $_SESSION)) {
            return false;
        }
        if ($clientConfigId !== $_SESSION['access_token']->getClientConfigId()) {
            return false;
        }
        if ($userId !== $_SESSION['access_token']->getUserId()) {
            return false;
        }
        if (!$_SESSION['access_token']->hasScope($scope)) {
            return false;
        }

        return $_SESSION['access_token'];
    }

    public function storeAccessToken(AccessToken $accessToken)
    {
        if (array_key_exists("access_token", $_SESSION)) {
            return false;
        }
        $_SESSION['access_token'] = $accessToken;

        return true;
    }

    public function deleteAccessToken(AccessToken $accessToken)
    {
        if (!array_key_exists("access_token", $_SESSION)) {
            return false;
        }
        if ($accessToken->getAccessToken() !== $_SESSION['access_token']->getAccessToken()) {
            return false;
        }
        unset($_SESSION['access_token']);

        return true;
    }

    public function getRefreshToken($clientConfigId, $userId, $scope)
    {
        if (!array_key_exists("refresh_token", $_SESSION)) {
            return false;
        }
        if ($clientConfigId !== $_SESSION['refresh_token']->getClientConfigId()) {
            return false;
        }
        if ($userId !== $_SESSION['refresh_token']->getUserId()) {
            return false;
        }
        if (!$_SESSION['refresh_token']->hasScope($scope)) {
            return false;
        }

        return $_SESSION['refresh_token'];
    }

    public function storeRefreshToken(RefreshToken $refreshToken)
    {
        if (array_key_exists("refresh_token", $_SESSION)) {
            return false;
        }
        $_SESSION['refresh_token'] = $refreshToken;

        return true;
    }

    public function deleteRefreshToken(RefreshToken $refreshToken)
    {
        if (!array_key_exists("refresh_token", $_SESSION)) {
            return false;
        }
        if ($refreshToken->getRefreshToken() !== $_SESSION['refresh_token']->getRefreshToken()) {
            return false;
        }
        unset($_SESSION['refresh_token']);

        return true;
    }

    public function getState($clientConfigId, $state)
    {
        if (!array_key_exists("state", $_SESSION)) {
            echo "no state session var";

            return false;
        }
        if ($clientConfigId !== $_SESSION['state']->getClientConfigId()) {
            echo "mismatch in configid";

            return false;
        }

        if ($state !== $_SESSION['state']->getState()) {
            echo "mismatch in state value";

            return false;
        }

        return $_SESSION['state'];
    }

    public function storeState(State $state)
    {
        if (array_key_exists("state", $_SESSION)) {
            //return false;
        }
        $_SESSION['state'] = $state;

        return true;
    }

    public function deleteStateForUser($clientConfigId, $userId)
    {
        if (!array_key_exists("state", $_SESSION)) {
            return false;
        }
        if ($clientConfigId !== $_SESSION['state']->getClientConfigId()) {
            return false;
        }
        if ($userId !== $_SESSION['state']->getUserId()) {
            return false;
        }

        unset($_SESSION['state']);

        return true;
    }

    public function deleteState(State $state)
    {
        if (!array_key_exists("state", $_SESSION)) {
            return false;
        }
        if ($state->getState() !== $_SESSION['state']->getState()) {
            return false;
        }
        unset($_SESSION['state']);

        return true;
    }
}
