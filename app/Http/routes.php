<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

$app->get('/', 'App\Http\Controllers\HomeController@index');
$app->post('/api/init', 'App\Http\Controllers\ApiController@init');
$app->post('/api/search', 'App\Http\Controllers\ApiController@search');
$app->post('/api/finalize_result', 'App\Http\Controllers\ApiController@finalizeResult');
$app->get('/api/download', 'App\Http\Controllers\ApiController@download');
