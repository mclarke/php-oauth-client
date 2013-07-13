<?php

namespace fkooman\OAuth\Client;

interface StorageInterface
{
    public function getAccessToken($clientConfigId, $userId, $scope);
    public function storeAccessToken(AccessToken $accessToken);
    public function updateAccessToken(AccessToken $accessToken, AccessToken $newAccessToken);
    public function deleteAccessToken(AccessToken $accessToken);
    public function invalidateAccessToken(AccessToken $accessToken);
    public function getState($clientConfigId, $state);
    public function storeState(State $state);
    public function deleteExistingState($clientConfigId, $userId);
    public function deleteState(State $state);

}
