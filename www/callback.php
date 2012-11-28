<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "SplClassLoader.php";

$c1 = new SplClassLoader("RestService", "../extlib/php-rest-service/lib");
$c1->register();
$c3 = new SplClassLoader("OAuth\\Client", "../lib");
$c3->register();

use \RestService\Http\HttpRequest as HttpRequest;
use \RestService\Http\HttpResponse as HttpResponse;
use \RestService\Http\IncomingHttpRequest as IncomingHttpRequest;
use \RestService\Utils\Config as Config;
use \RestService\Utils\Logger as Logger;

use \OAuth\Client\Callback as Callback;
use \OAuth\Client\CallbackException as CallbackException;

$logger = NULL;
$request = NULL;
$response = NULL;

try {
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini");
    $logger = new Logger($config->getSectionValue('Log', 'logLevel'), $config->getValue('serviceName'), $config->getSectionValue('Log', 'logFile'), $config->getSectionValue('Log', 'logMail', FALSE));

    $service = new Callback($config, $logger);

    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());
    $request->matchRest("GET", "/:callbackId", function($callbackId) use ($request, &$response, $service) {
        $response = $service->handleRequest($callbackId, $request);
    });
    $request->matchRestDefault(function($methodMatch, $patternMatch) use ($request) {
        if (in_array($request->getRequestMethod(), $methodMatch)) {
            if (!$patternMatch) {
                throw new FooException("not_found", "resource not found");
            }
        } else {
            throw new FooException("method_not_allowed", "request method not allowed");
        }
    });

} catch (CallbackException $e) {
    $response = new HttpResponse(400);
    $response->setContent($e->getMessage());
    if (NULL !== $logger) {
        $logger->logWarn($e->getMessage() . PHP_EOL . $request . PHP_EOL . $response);
    }
} catch (Exception $e) {
    $response = new HttpResponse(500);
    $response->setHeader("Content-Type", "application/json");
    $response->setContent(json_encode(array("error" => "internal_server_error", "error_description" => $e->getMessage())));
    if (NULL !== $logger) {
        $logger->logFatal($e->getMessage() . PHP_EOL . $request . PHP_EOL . $response);
    }
}

if (NULL !== $logger) {
    $logger->logDebug($request);
}
if (NULL !== $logger) {
    $logger->logDebug($response);
}
if (NULL !== $response) {
    $response->sendResponse();
}
