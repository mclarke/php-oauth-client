<?php

namespace fkooman\OAuth\Client;

interface StorageInterface
{
    public function storeAccessToken(AccessToken $accessToken);
    public function getAccessToken($clientConfigId, $userId, $scope);
    public function deleteAccessToken(AccessToken $accessToken);

    public function storeRefreshToken(RefreshToken $refreshToken);
    public function getRefreshToken($clientConfigId, $userId, $scope);
    public function deleteRefreshToken(RefreshToken $refreshToken);

    public function storeState(State $state);
    public function getState($clientConfigId, $state);
    public function deleteState(State $state);
    public function deleteStateForUser($clientConfigId, $userId);
}
