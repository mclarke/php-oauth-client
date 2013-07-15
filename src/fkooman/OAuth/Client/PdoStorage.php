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

class PdoStorage implements StorageInterface
{
    private $pdo;

    public function __construct(PDO $p)
    {
        $this->pdo = $p;
    }

    public function getAccessToken($clientConfigId, $userId, $scope)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM oauth_access_tokens WHERE client_config_id = :client_config_id AND user_id = :user_id AND scope = :scope");
        $stmt->bindValue(":client_config_id", $clientConfigId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (false !== $result) ? AccessToken::fromArray($result) : false;
    }

    public function storeAccessToken(AccessToken $accessToken)
    {
        $token = $accessToken->getToken();

        $stmt = $this->pdo->prepare("INSERT INTO oauth_access_tokens (client_config_id, user_id, scope, access_token, token_type, expires_in, issue_time) VALUES(:client_config_id, :user_id, :scope, :access_token, :token_type, :expires_in, :issue_time)");
        $stmt->bindValue(":client_config_id", $accessToken->getclientConfigId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $accessToken->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":scope", $token->getScope(), PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $token->getAccessToken(), PDO::PARAM_STR);
        $stmt->bindValue(":token_type", $token->getTokenType(), PDO::PARAM_STR);
        $stmt->bindValue(":expires_in", $token->getExpiresIn(), PDO::PARAM_INT);
        $stmt->bindValue(":issue_time", $accessToken->getIssueTime(), PDO::PARAM_INT);

        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function deleteAccessToken(AccessToken $accessToken)
    {
        $stmt = $this->pdo->prepare("DELETE FROM oauth_access_tokens WHERE client_config_id = :client_config_id AND user_id = :user_id AND access_token = :access_token");
        $stmt->bindValue(":client_config_id", $accessToken->getclientConfigId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $accessToken->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $accessToken->getToken()->getAccessToken(), PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function getState($clientConfigId, $state)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM oauth_states WHERE client_config_id = :client_config_id AND state = :state");
        $stmt->bindValue(":client_config_id", $clientConfigId, PDO::PARAM_STR);
        $stmt->bindValue(":state", $state, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (false !== $result) ? State::fromArray($result) : false;
    }

    public function storeState(State $state)
    {
        $stmt = $this->pdo->prepare("INSERT INTO oauth_states (client_config_id, user_id, scope, state) VALUES(:client_config_id, :user_id, :scope, :state)");
        $stmt->bindValue(":client_config_id", $state->getclientConfigId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $state->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":scope", $state->getScope(), PDO::PARAM_STR);
        $stmt->bindValue(":state", $state->getState(), PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function deleteStateForUser($clientConfigId, $userId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM oauth_states WHERE client_config_id = :client_config_id AND user_id = :user_id");
        $stmt->bindValue(":client_config_id", $clientConfigId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function deleteState(State $state)
    {
        $stmt = $this->pdo->prepare("DELETE FROM oauth_states WHERE client_config_id = :client_config_id AND state = :state");
        $stmt->bindValue(":client_config_id", $state->getclientConfigId(), PDO::PARAM_STR);
        $stmt->bindValue(":state", $state->getState(), PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function dbQuery($query)
    {
        $this->pdo->exec($query);
    }
}
