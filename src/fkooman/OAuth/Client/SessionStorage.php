<?php

namespace fkooman\OAuth\Client;

class SessionStorage implements StorageInterface
{
    public function __construct()
    {
        if ("" === session_id()) {
            // no session exists yet
            session_start();
        }
    }

    public function getAccessToken($clientConfigId, $userId, $scope)
    {
        if (!array_key_exists("access_token", $_SESSION)) {
            return false;
        }
        $sessionAccessToken = unserialize($_SESSION['access_token']);

        if ($clientConfigId !== $sessionAccessToken->getClientConfigId()) {
            return false;
        }
        if ($userId !== $sessionAccessToken->getUserId()) {
            return false;
        }
        if (!$sessionAccessToken->hasScope($scope)) {
            return false;
        }

        return $sessionAccessToken;
    }

    public function storeAccessToken(AccessToken $accessToken)
    {
        if (array_key_exists("access_token", $_SESSION)) {
            return false;
        }
        $_SESSION['access_token'] = serialize($accessToken);

        return true;
    }

    public function deleteAccessToken(AccessToken $accessToken)
    {
        if (!array_key_exists("access_token", $_SESSION)) {
            return false;
        }
        $sessionAccessToken = unserialize($_SESSION['access_token']);

        if ($accessToken->getAccessToken() !== $sessionAccessToken->getAccessToken()) {
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
        $sessionRefreshToken = unserialize($_SESSION['refresh_token']);

        if ($clientConfigId !== $sessionRefreshToken->getClientConfigId()) {
            return false;
        }
        if ($userId !== $sessionRefreshToken->getUserId()) {
            return false;
        }
        if (!$sessionRefreshToken->hasScope($scope)) {
            return false;
        }

        return $sessionRefreshToken;
    }

    public function storeRefreshToken(RefreshToken $refreshToken)
    {
        if (array_key_exists("refresh_token", $_SESSION)) {
            return false;
        }
        $_SESSION['refresh_token'] = serialize($refreshToken);

        return true;
    }

    public function deleteRefreshToken(RefreshToken $refreshToken)
    {
        if (!array_key_exists("refresh_token", $_SESSION)) {
            return false;
        }
        $sessionRefreshToken = unserialize($_SESSION['refresh_token']);

        if ($refreshToken->getRefreshToken() !== $sessionRefreshToken->getRefreshToken()) {
            return false;
        }
        unset($_SESSION['refresh_token']);

        return true;
    }

    public function getState($clientConfigId, $state)
    {
        if (!array_key_exists("state", $_SESSION)) {
            return false;
        }
        $sessionState = unserialize($_SESSION['state']);

        if ($clientConfigId !== $sessionState->getClientConfigId()) {
            return false;
        }

        if ($state !== $sessionState->getState()) {
            return false;
        }

        return $sessionState;
    }

    public function storeState(State $state)
    {
        if (array_key_exists("state", $_SESSION)) {
            return false;
        }
        $_SESSION['state'] = serialize($state);

        return true;
    }

    public function deleteStateForUser($clientConfigId, $userId)
    {
        if (!array_key_exists("state", $_SESSION)) {
            return false;
        }
        $sessionState = unserialize($_SESSION['state']);

        if ($clientConfigId !== $sessionState->getClientConfigId()) {
            return false;
        }
        if ($userId !== $sessionState->getUserId()) {
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
        $sessionState = unserialize($_SESSION['state']);

        if ($state->getState() !== $sessionState->getState()) {
            return false;
        }
        unset($_SESSION['state']);

        return true;
    }
}
