<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Http\Handlers;
use App\Http\Middleware;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Laravel\Lumen\Application;
use Laravel\Lumen\Routing\Router;

(new Dotenv\Dotenv(__DIR__ . '/../env'))->load();

$app = new Application();
$app->singleton(ExceptionHandler::class, App\Exceptions\Handler::class);
$app->routeMiddleware([
    Middleware\AuthMiddleware::NAME => Middleware\AuthMiddleware::class,
]);

$app->register(App\Providers\AppServiceProvider::class);

$app->router->get('/', function (Application $app) {
    return [];
});

$app->router->group(['prefix' => '/api', 'middleware' => Middleware\AuthMiddleware::NAME], function (Router $route) {
    $route->get('/', Handlers\HomeHandler::class);
    $route->post('/', Handlers\ClickHandler::class);
});

return $app;
