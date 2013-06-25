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

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (FALSE !== $result) ? AccessToken::fromArray($result) : FALSE;
    }

    public function storeAccessToken(AccessToken $accessToken)
    {
        $token = $accessToken->getToken();

        $stmt = $this->_pdo->prepare("INSERT INTO oauth_access_tokens (callback_id, user_id, scope, access_token, token_type, expires_in, refresh_token, issue_time, is_usable) VALUES(:callback_id, :user_id, :scope, :access_token, :token_type, :expires_in, :refresh_token, :issue_time, :is_usable)");
        $stmt->bindValue(":callback_id", $accessToken->getCallbackId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $accessToken->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":scope", $token->getScope(), PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $token->getAccessToken(), PDO::PARAM_STR);
        $stmt->bindValue(":token_type", $token->getTokenType(), PDO::PARAM_STR);
        $stmt->bindValue(":expires_in", $token->getExpiresIn(), PDO::PARAM_INT);
        $stmt->bindValue(":refresh_token", $token->getRefreshToken(), PDO::PARAM_STR);
        $stmt->bindValue(":issue_time", $accessToken->getIssueTime(), PDO::PARAM_INT);
        $stmt->bindValue(":is_usable", $accessToken->getIsUsable(), PDO::PARAM_BOOL);

        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function updateAccessToken(AccessToken $accessToken, AccessToken $newAccessToken)
    {
        $stmt = $this->_pdo->prepare("
            UPDATE oauth_access_tokens
            SET
                access_token = :access_token,
                token_type = :token_type,
                scope = :scope,
                expires_in = :expires_in,
                refresh_token = :refresh_token,
                issue_time = :issue_time,
                is_usable = :is_usable
            WHERE
                callback_id = :callback_id
                    AND user_id = :user_id
                    AND scope = :previous_scope
        ");

        // if new token does not have a new refresh token, take the old one
        $refreshToken = (NULL !== $newAccessToken->getToken()->getRefreshToken()) ? $newAccessToken->getToken()->getRefreshToken() : $accessToken->getToken()->getRefreshToken();

        $stmt->bindValue(":access_token", $newAccessToken->getToken()->getAccessToken(), PDO::PARAM_STR);
        $stmt->bindValue(":token_type", $newAccessToken->getToken()->getTokenType(), PDO::PARAM_STR);
        $stmt->bindValue(":expires_in", $newAccessToken->getToken()->getExpiresIn(), PDO::PARAM_INT);
        $stmt->bindValue(":refresh_token", $refreshToken, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $newAccessToken->getToken()->getScope(), PDO::PARAM_STR);
        $stmt->bindValue(":issue_time", $newAccessToken->getIssueTime(), PDO::PARAM_INT);
        $stmt->bindValue(":is_usable", $newAccessToken->getIsUsable(), PDO::PARAM_BOOL);

        $stmt->bindValue(":callback_id", $accessToken->getCallbackId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $accessToken->getUserId(), PDO::PARAM_STR);

        // the scope retrieved using the refresh_token should really be the same as
        // the scope from the initial token, we don't really deal with any other situation...
        $stmt->bindValue(":previous_scope", $accessToken->getToken()->getScope(), PDO::PARAM_STR);

        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function deleteAccessToken(AccessToken $accessToken)
    {
        $stmt = $this->_pdo->prepare("DELETE FROM oauth_access_tokens WHERE callback_id = :callback_id AND user_id = :user_id AND access_token = :access_token");
        $stmt->bindValue(":callback_id", $accessToken->getCallbackId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $accessToken->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $accessToken->getToken()->getAccessToken(), PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function invalidateAccessToken(AccessToken $accessToken)
    {
        $stmt = $this->_pdo->prepare("UPDATE oauth_access_tokens SET is_usable = :is_usable WHERE callback_id = :callback_id AND user_id = :user_id AND access_token = :access_token");
        $stmt->bindValue(":is_usable", FALSE, PDO::PARAM_BOOL);
        $stmt->bindValue(":callback_id", $accessToken->getCallbackId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $accessToken->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $accessToken->getToken()->getAccessToken(), PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function getState($callbackId, $state)
    {
        $stmt = $this->_pdo->prepare("SELECT * FROM oauth_states WHERE callback_id = :callback_id AND state = :state");
        $stmt->bindValue(":callback_id", $callbackId, PDO::PARAM_STR);
        $stmt->bindValue(":state", $state, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (FALSE !== $result) ? State::fromArray($result) : FALSE;
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
