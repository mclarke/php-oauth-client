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

require_once __DIR__ . '/../vendor/autoload.php';

use fkooman\Config\Config;
use fkooman\OAuth\Client\Callback;
use fkooman\OAuth\Client\PdoStorage;
use Symfony\Component\HttpFoundation\Request;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// FIXME: use (or just Silex's DI stuff to feed Callback)

$app = new Silex\Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));
$app['config'] = function() {
    $configFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.yaml";

    return Config::fromYamlFile($configFile);
};
$app['storage'] = function($c) {
    return new PdoStorage($c['config']->s("storage"));
};
$app['log'] = function($c) {
    $l = new Logger($c['config']->l('name'));
    $l->pushHandler(new StreamHandler($c['config']->s('log')->l('file', false, NULL), $c['config']->s('log')->l('level', false, 400)));

    return $l;
};

$app->get('/', function(Request $request) use ($app) {
    $service = new Callback($app);
    $returnUri = $service->handleCallback($request);

    return $app->redirect($returnUri);
});

$app->error(function(\Exception $e, $code) use ($app) {
    // FIXME: LOGGING
});

$app->error(function(\Exception $e, $code) use ($app) {
    return $app['twig']->render('error.twig', array(
        'code' => $code,
        'message' => $e->getMessage(),
    ));
});

$app->run();
