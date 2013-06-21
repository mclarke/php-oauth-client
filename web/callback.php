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

use fkooman\OAuth\Client\Callback;
use Symfony\Component\HttpFoundation\Request;

$di = new \fkooman\OAuth\Client\DiContainer();
$app = new Silex\Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app->get('/', function(Request $request) use ($app, $di) {
    $service = new Callback($di);
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
