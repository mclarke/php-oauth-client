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
