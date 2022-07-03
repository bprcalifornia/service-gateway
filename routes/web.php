<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// GitHub-specific service routes with the /github prefix
$router->group(['prefix' => 'github'], function() use ($router) {
    // make sure we enforce request validation using webhook secrets
    $router->group(['middleware' => 'github-event'], function() use ($router) {
        $router->post('raw-event', 'GitHubController@receiveRawEvent');
    });
});