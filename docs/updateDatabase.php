<?php

require_once 'lib/SplClassLoader.php';

$c1 = new SplClassLoader("RestService", "extlib/php-rest-service/lib");
$c1->register();

$c2 =  new SplClassLoader("OAuth\\Client", "lib");
$c2->register();

use \RestService\Utils\Config as Config;
use \OAuth\Client\PdoStorage as PdoStorage;

/**
 * The database updater class
 */
class DbUpdate extends PdoStorage
{
    public function updateDb()
    {
        $this->_pdo->exec("ALTER TABLE oauth_states ADD COLUMN scope TEXT DEFAULT NULL");
    }

}

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini");

$storage = new DbUpdate($config);
$storage->updateDb();
