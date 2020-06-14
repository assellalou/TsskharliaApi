<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require 'vendor/autoload.php';
require 'core/bootstrap.php';

use App\Controllers\UsersController;
use App\Core\{Router, Request, App};
use App\Core\Database\QueryBuilder;

if (!UsersController::isConnected() && Request::uri() != 'login' && Request::uri() != 'register') {
    Router::respond(0, 401, 'Unauthorized', ['Redirect' => '/login']);
    exit;
} else {
    Router::load('app/routes.php')->direct(Request::uri(), Request::method());
}
