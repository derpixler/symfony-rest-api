<?php

namespace DerPixler\Symfony\RestApi;

require_once __DIR__ . '/../vendor/autoload.php';

use DerPixler\Symfony\RestApi\Controllers\RestController;
use Exception;

use Symfony\Component\
{
    HttpFoundation\Request,
    HttpFoundation\Response,

    Routing\Matcher\UrlMatcher,
    Routing\Route,
    Routing\RouteCollection,
    Routing\RequestContext,

    HttpKernel\Controller\ArgumentResolver,
    HttpKernel\Controller\ControllerResolver,
};

$route = new Route(
    '/rest-api/{client}/{action}',
    [
        '_controller' => RestController::class,
        'client' => '',
        'action' => ''
    ]
);
$route->setMethods(['POST']);

$routes = new RouteCollection();
$routes->add('api', $route);

$request = Request::createFromGlobals();
$context = new RequestContext();
$context->fromRequest($request);

$matcher = new UrlMatcher($routes, $context);
$controllerResolver = new ControllerResolver();
$argumentResolver = new ArgumentResolver();

try {
    $request->attributes->add($matcher->match($request->getPathInfo()));
    $controller = $controllerResolver->getController($request);
    $arguments = $argumentResolver->getArguments($request, $controller);

    $response = call_user_func_array($controller, $arguments);
} catch (\Symfony\Component\Routing\Exception\ResourceNotFoundException $exception) {
    $response = new Response('Not Found', 404);
} catch (Exception $exception) {
    $response = new Response('An error occurred', 500);
}

$response->send();
