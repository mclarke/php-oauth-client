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

use \PDO as PDO;

class PdoStorage
{
    private $_pdo;

    public function __construct(PDO $p)
    {
        $this->_pdo = $p;
    }

    public function getAccessToken($callbackId, $userId, $scope)
    {
        $stmt = $this->_pdo->prepare("SELECT * FROM oauth_access_tokens WHERE callback_id = :callback_id AND user_id = :user_id AND scope = :scope");
        $stmt->bindValue(":callback_id", $callbackId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function storeAccessToken(AccessTokenContainer $accessTokenContainer)
    {
        $accessToken = $accessTokenContainer->getAccessToken();

        $stmt = $this->_pdo->prepare("INSERT INTO oauth_access_tokens (callback_id, user_id, scope, access_token, token_type, expires_in, refresh_token, issue_time, is_usable) VALUES(:callback_id, :user_id, :scope, :access_token, :token_type, :expires_in, :refresh_token, :issue_time, :is_usable)");
        $stmt->bindValue(":callback_id", $accessTokenContainer->getCallbackId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $accessTokenContainer->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":scope", $accessToken->getScope(), PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $accessToken->getAccessToken(), PDO::PARAM_STR);
        $stmt->bindValue(":token_type", $accessToken->getTokenType(), PDO::PARAM_STR);
        $stmt->bindValue(":expires_in", $accessToken->getExpiresIn(), PDO::PARAM_INT);
        $stmt->bindValue(":refresh_token", $accessToken->getRefreshToken(), PDO::PARAM_STR);
        $stmt->bindValue(":issue_time", $accessTokenContainer->getIssueTime(), PDO::PARAM_INT);
        $stmt->bindValue(":is_usable", $accessTokenContainer->getIsUsable(), PDO::PARAM_BOOL);

        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function deleteAccessToken(AccessTokenContainer $a)
    {
        $stmt = $this->_pdo->prepare("DELETE FROM oauth_access_tokens WHERE callback_id = :callback_id AND user_id = :user_id AND access_token = :access_token");
        $stmt->bindValue(":callback_id", $a->getCallbackId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $a->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $a->getAccessToken(), PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function getState($callbackId, $state)
    {
        $stmt = $this->_pdo->prepare("SELECT * FROM oauth_states WHERE callback_id = :callback_id AND state = :state");
        $stmt->bindValue(":callback_id", $callbackId, PDO::PARAM_STR);
        $stmt->bindValue(":state", $state, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function storeState(State $state)
    {
        $stmt = $this->_pdo->prepare("INSERT INTO oauth_states (callback_id, user_id, scope, return_uri, state) VALUES(:callback_id, :user_id, :scope, :return_uri, :state)");
        $stmt->bindValue(":callback_id", $state->getCallbackId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $state->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":scope", $state->getScope(), PDO::PARAM_STR);
        $stmt->bindValue(":return_uri", $state->getReturnUri(), PDO::PARAM_STR);
        $stmt->bindValue(":state", $state->getState(), PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function deleteExistingState($callbackId, $userId)
    {
        $stmt = $this->_pdo->prepare("DELETE FROM oauth_states WHERE callback_id = :callback_id AND user_id = :user_id");
        $stmt->bindValue(":callback_id", $callbackId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function deleteState(State $state)
    {
        $stmt = $this->_pdo->prepare("DELETE FROM oauth_states WHERE callback_id = :callback_id AND state = :state");
        $stmt->bindValue(":callback_id", $state->getCallbackId(), PDO::PARAM_STR);
        $stmt->bindValue(":state", $state->getState(), PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function dbQuery($query)
    {
        $this->_pdo->exec($query);
    }

}
