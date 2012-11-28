<?php

namespace OAuth\Client;

use \RestService\Utils\Config as Config;
use \PDO as PDO;

class PdoStorage
{
    private $_c;
    private $_pdo;

    public function __construct(Config $c)
    {
        $this->_c = $c;

        $driverOptions = array();
        if ($this->_c->getSectionValue('PdoStorage', 'persistentConnection')) {
            $driverOptions = array(PDO::ATTR_PERSISTENT => TRUE);
        }

        $this->_pdo = new PDO($this->_c->getSectionValue('PdoStorage', 'dsn'), $this->_c->getSectionValue('PdoStorage', 'username', FALSE), $this->_c->getSectionValue('PdoStorage', 'password', FALSE), $driverOptions);

        if (0 === strpos($this->_c->getSectionValue('PdoStorage', 'dsn'), "sqlite:")) {
            // only for SQlite
            $this->_pdo->exec("PRAGMA foreign_keys = ON");
        }
    }

    public function getAccessToken($callbackId, $userId, $scope)
    {
        $stmt = $this->_pdo->prepare("SELECT * FROM oauth_access_tokens WHERE callback_id = :callback_id AND user_id = :user_id AND scope = :scope");
        $stmt->bindValue(":callback_id", $callbackId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to get access token", var_export($this->_pdo->errorInfo(), TRUE));
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function storeAccessToken($callbackId, $userId, $scope, $accessToken, $issueTime, $expiresIn)
    {
        $stmt = $this->_pdo->prepare("INSERT INTO oauth_access_tokens (callback_id, user_id, scope, access_token, issue_time, expires_in) VALUES(:callback_id, :user_id, :scope, :access_token, :issue_time, :expires_in)");
        $stmt->bindValue(":callback_id", $callbackId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $accessToken, PDO::PARAM_STR);
        $stmt->bindValue(":issue_time", $issueTime, PDO::PARAM_INT);
        $stmt->bindValue(":expires_in", $expiresIn, PDO::PARAM_INT);
        if (FALSE === $stmt->execute() || 1 !== $stmt->rowCount()) {
            throw new StorageException("unable to store access token", var_export($this->_pdo->errorInfo(), TRUE));
        }
    }

    public function deleteAccessToken($callbackId, $userId, $accessToken)
    {
        $stmt = $this->_pdo->prepare("DELETE FROM oauth_access_tokens WHERE callback_id = :callback_id AND user_id = :user_id AND access_token = :access_token");
        $stmt->bindValue(":callback_id", $callbackId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $accessToken, PDO::PARAM_STR);
        if (FALSE === $stmt->execute() || 1 !== $stmt->rowCount()) {
            throw new StorageException("unable to delete access token", var_export($this->_pdo->errorInfo(), TRUE));
        }
    }

    public function getRefreshToken($callbackId, $userId, $scope)
    {
        $stmt = $this->_pdo->prepare("SELECT * FROM oauth_refresh_tokens WHERE callback_id = :callback_id AND user_id = :user_id AND scope = :scope");
        $stmt->bindValue(":callback_id", $callbackId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to get refresh token", var_export($this->_pdo->errorInfo(), TRUE));
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function storeRefreshToken($callbackId, $userId, $scope, $refreshToken)
    {
        $stmt = $this->_pdo->prepare("INSERT INTO oauth_refresh_tokens (callback_id, user_id, scope, refresh_token) VALUES(:callback_id, :user_id, :scope, :refresh_token)");
        $stmt->bindValue(":callback_id", $callbackId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $stmt->bindValue(":refresh_token", $refreshToken, PDO::PARAM_STR);
        if (FALSE === $stmt->execute() || 1 !== $stmt->rowCount()) {
            throw new StorageException("unable to store refresh token", var_export($this->_pdo->errorInfo(), TRUE));
        }
    }

    public function deleteRefreshToken($callbackId, $userId, $refreshToken)
    {
        $stmt = $this->_pdo->prepare("DELETE FROM oauth_refresh_tokens WHERE callback_id = :callback_id AND user_id = :user_id AND refresh_token = :refresh_token");
        $stmt->bindValue(":callback_id", $callbackId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->bindValue(":refresh_token", $refreshToken, PDO::PARAM_STR);
        if (FALSE === $stmt->execute() || 1 !== $stmt->rowCount()) {
            throw new StorageException("unable to delete refresh token", var_export($this->_pdo->errorInfo(), TRUE));
        }
    }

    public function getState($callbackId, $state)
    {
        $stmt = $this->_pdo->prepare("SELECT * FROM oauth_states WHERE callback_id = :callback_id AND state = :state");
        $stmt->bindValue(":callback_id", $callbackId, PDO::PARAM_STR);
        $stmt->bindValue(":state", $state, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to get state", var_export($this->_pdo->errorInfo(), TRUE));
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function storeState($callbackId, $userId, $state, $returnUri)
    {
        $stmt = $this->_pdo->prepare("INSERT INTO oauth_states (callback_id, user_id, state, return_uri) VALUES(:callback_id, :user_id, :state, :return_uri)");
        $stmt->bindValue(":callback_id", $callbackId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->bindValue(":state", $state, PDO::PARAM_STR);
        $stmt->bindValue(":return_uri", $returnUri, PDO::PARAM_STR);
        if (FALSE === $stmt->execute() || 1 !== $stmt->rowCount()) {
            throw new StorageException("unable to store state", var_export($this->_pdo->errorInfo(), TRUE));
        }
    }

    public function deleteState($callbackId, $state)
    {
        $stmt = $this->_pdo->prepare("DELETE FROM oauth_states WHERE callback_id = :callback_id AND state = :state");
        $stmt->bindValue(":callback_id", $callbackId, PDO::PARAM_STR);
        $stmt->bindValue(":state", $state, PDO::PARAM_STR);
        if (FALSE === $stmt->execute() || 1 !== $stmt->rowCount()) {
            throw new StorageException("unable to delete state", var_export($this->_pdo->errorInfo(), TRUE));
        }
    }

    public function initDatabase()
    {
        // states
        $result = $this->_pdo->exec("
            CREATE TABLE IF NOT EXISTS oauth_states (
                callback_id VARCHAR(64) NOT NULL,
                user_id VARCHAR(64) NOT NULL,
                state VARCHAR(64) NOT NULL,
                return_uri TEXT NOT NULL,
                UNIQUE (callback_id, user_id),
                PRIMARY KEY (state)
            )
        ");

        // access_tokens
        $result = $this->_pdo->exec("
            CREATE TABLE IF NOT EXISTS oauth_access_tokens (
                callback_id VARCHAR(64) NOT NULL,
                user_id VARCHAR(64) NOT NULL,
                access_token VARCHAR(64) NOT NULL,
                issue_time INT(11) NOT NULL,
                expires_in INT(11) DEFAULT NULL,
                scope TEXT DEFAULT NULL,
                UNIQUE (callback_id, user_id)
            )
        ");

        // refresh_tokens
        $result = $this->_pdo->exec("
            CREATE TABLE IF NOT EXISTS oauth_refresh_tokens (
                callback_id VARCHAR(64) NOT NULL,
                user_id VARCHAR(64) NOT NULL,
                refresh_token VARCHAR(64) NOT NULL,
                scope TEXT DEFAULT NULL,
                UNIQUE (callback_id, user_id)
            )
        ");
    }

}
