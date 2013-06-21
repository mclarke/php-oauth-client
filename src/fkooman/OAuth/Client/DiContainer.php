<?php

namespace fkooman\OAuth\Client;

class DiContainer extends \Silex\Application
{
    public function __construct()
    {
        $this['config'] = function() {
            $configFile = dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.yaml";

            return \fkooman\Config\Config::fromYamlFile($configFile);
        };

        $this['db'] = function($c) {
            $config = $c['config']->s('storage');

            $driverOptions = array();
            $persistent = $config->l('persistentConnection', FALSE, FALSE);
            if ($persistent) {
                $driverOptions = array(\PDO::ATTR_PERSISTENT => TRUE);
            }

            $dsn = $config->l('dsn', TRUE);
            $username = $config->l('username', FALSE);
            $password = $config->l('password', FALSE);

            $db = new \PDO($dsn, $username, $password, $driverOptions);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            if (0 === strpos($dsn, "sqlite:")) {
                // only for SQlite
                $db->exec("PRAGMA foreign_keys = ON");
            }

            $storage = new PdoStorage($db);

            return $storage;
        };

        $this['log'] = function($c) {
            $config = $c['config']->s('log');

            $log = new \Monolog\Logger($c['config']->l('name'));
            $log->pushHandler(new \Monolog\Handler\StreamHandler($config->l('file', FALSE, NULL), $config->l('level', FALSE, 400)));

            return $log;
        };

        $this['http'] = function($c) {
            $guzzle = new \Guzzle\Http\Client();
            $logPlugin = new \Guzzle\Plugin\Log\LogPlugin(new \Guzzle\Log\PsrLogAdapter($c['log']), \Guzzle\Log\MessageFormatter::DEBUG_FORMAT);
            $guzzle->addSubscriber($logPlugin);

            return $guzzle;
        };
    }
}
