<?php

namespace OAuth\Client;

use \RestService\Utils\Config as Config;
use \RestService\Utils\Logger as Logger;
use \RestService\Http\HttpResponse as HttpResponse;

class Api
{
    private $_callbackId;

    private $_userId;
    private $_scope;
    private $_returnUri;

    private $_c;
    private $_logger;
    private $_storage;

    public function __construct($callbackId)
    {
        $this->_callbackId = $callbackId;

        $this->_userId = NULL;
        $this->_scope = NULL;
        $this->_returnUri = NULL;

        $this->_c = new Config(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini");
        $this->_logger = new Logger($this->_c->getSectionValue('Log', 'logLevel'), $this->_c->getValue('serviceName'), $this->_c->getSectionValue('Log', 'logFile'), $this->_c->getSectionValue('Log', 'logMail', FALSE));

        $this->_storage = new PdoStorage($this->_c);
    }

    public function setScope($scope)
    {
        $this->_scope = $scope;
    }

    public function setUserId($userId)
    {
        $this->_userId = $userId;
    }

    public function setReturnUri($returnUri)
    {
        $this->_returnUri = $returnUri;
    }

    public function getAccessToken()
    {
        // check if callBackId exists in config
        // FIXME: better validation of config file reading...
        $configuredClientsFile = $this->_c->getSectionValue('OAuth', 'clientList');
        $configuredClientsJson = file_get_contents($configuredClientsFile);
        $clients = json_decode($configuredClientsJson, TRUE);
        if (!is_array($clients) || !array_key_exists($this->_callbackId, $clients)) {
            throw new ApiException("invalid callback id");
        }

        // check if access token is actually available for this user, if
        $token = $this->_storage->getAccessToken($this->_callbackId, $this->_userId, $this->_scope);

        if (!empty($token)) {
            if (NULL === $token['expires_in']) {
                // no known expires_in, so assume token is valid
                return $token['access_token'];
            }
            if ($token['issue_time'] + $token['expires_in'] > time()) {
                // appears valid
                return $token['access_token'];
            }
            $this->_storage->deleteAccessToken($this->_callbackId, $this->_userId, $token['access_token']);
        }

        // no access token
        $client = $clients[$this->_callbackId];

        // store state
        $state = bin2hex(openssl_random_pseudo_bytes(8));
        try {
            $this->_storage->storeState($this->_callbackId, $this->_userId, $state, $this->_returnUri);
        } catch (StorageException $e) {
            echo $e->getMessage() . $e->getDescription();
            die();
        }
        $q = array (
            "client_id" => $client['client_id'],
            "response_type" => "code",
            "state" => $state,
        );
        if (NULL !== $this->_scope) {
            $q['scope'] = $this->_scope;
        }
        if (array_key_exists('redirect_uri', $client) && !empty($client['redirect_uri'])) {
            $q['redirect_uri'] = $client['redirect_uri'];
        }
        $separator = (FALSE === strpos($client['authorize_endpoint'], "?")) ? "?" : "&";
        $authorizeUri = $client['authorize_endpoint'] . $separator . http_build_query($q);
        $httpResponse = new HttpResponse(302);
        $httpResponse->setHeader("Location", $authorizeUri);
        $httpResponse->sendResponse();
        exit;
    }

}
