<?php

namespace DerPixler\Symfony\RestApi;

require_once __DIR__ . '/../vendor/autoload.php';

use DerPixler\Symfony\RestApi\Controllers\RestController;
use DerPixler\Symfony\RestApi\Entities\PostEntity;
use Exception;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Driver\PDO\MySql\Driver;
use Doctrine\DBAL\Configuration;
use Doctrine\ORM\ORMSetup;

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


$dbUrl = 'mysql://dbuser:dbpass@db/db';
$dbConfig = parse_url($dbUrl);

$config = new Configuration();
$connectionParams = [
    'dbname' => ltrim($dbConfig['path'], '/'),
    'host' => $dbConfig['host'],
    'port' => $dbConfig['port'] ?? 3306,
    'user' => $dbConfig['user'],
    'password' => $dbConfig['pass'],
    'driverClass' => Driver::class,
    'charset' => 'utf8mb4',
];

$connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
$annotationConfig = ORMSetup::createAnnotationMetadataConfiguration(
    [__DIR__ . '/src/Entities'],
    true
);

$entityManager = new EntityManager($connection, $annotationConfig);
$posts = $entityManager->getRepository(PostEntity::class)->findAll();

$route = new Route(
    '/rest-api/{client}/{action}',
    [
        '_controller' => RestController::class,
        'client' => '',
        'action' => ''
    ]
);
#$route->setMethods(['POST']);

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


    $entityManager = EntityManager::create($databaseConfig['db'], Setup::createAnnotationMetadataConfiguration([__DIR__ . '/src'], true));


    $response = call_user_func_array($controller, $arguments);
} catch (\Symfony\Component\Routing\Exception\ResourceNotFoundException $exception) {
    $response = new Response('Not Found', 404);
} catch (Exception $exception) {
    $response = new Response('An error occurred', 500);
}

$response->send();
