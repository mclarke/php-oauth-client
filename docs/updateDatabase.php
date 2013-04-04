<?php
require_once 'lib/_autoload.php';

use \RestService\Utils\Config as Config;
use \OAuth\Client\PdoStorage as PdoStorage;

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini");
$storage = new PdoStorage($config);

$d = $storage->getChangeInfo();
$patchLevel = FALSE === $d ? 0 : $d['patch_number'];

echo "[INFO] current database patch level: " . $patchLevel . PHP_EOL;

// look for all SQL patch files and execute them in order
foreach (glob("schema/updates/*.sql") as $filename) {
    list($patchNumber, $description) = explode("_", basename($filename, ".sql"), 2);
    if ($patchNumber > $patchLevel) {
        echo "[UPDATE] applying " . $filename . "..." . PHP_EOL;
        $sql = file_get_contents($filename);
        $storage->dbQuery($sql);
        $storage->addChangeInfo($patchNumber, $description);
    }
}
echo "[INFO] done applying patches!" . PHP_EOL;
