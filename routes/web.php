<?php

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

$app->get('/', function () use ($app) {
    return $app->version();
});

// $app->get('/url/{hash_id}', 'URLController@show');
// $app->get('/image/{hash_id}', 'ImageController@show');
// $app->post('/api', 'FailoverController@show');

$app->get('/test', 'EventController@test');
$app->get('/recommend', 'RecommendationController@show');
$app->get('/dummy_recommend', 'RecommendationController@dummy');
$app->post('/event', 'EventController@show');
$app->post('/simple_event', 'EventController@simpleShow');