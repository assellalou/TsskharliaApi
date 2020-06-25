<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require 'vendor/autoload.php';
require 'core/bootstrap.php';

use App\Controllers\UsersController;
use App\Core\{Router, Request};

if (!UsersController::isConnected() && Request::uri() != 'login' && Request::uri() != 'register' && Request::uri() != 'author') {
    Router::respond(0, 401, 'Unauthorized', ['Redirect' => '/login']);
    exit;
} else {
    Router::load('app/routes.php')->direct(Request::uri(), Request::method());
}

#-> Important legal terms
#--> If you are not a legal Devwave co-founder and never got a legal or parole waiver from the author/s of this project
#    you are not authorized to publish, reproduce or redistribute this project at any manner.
#--> If you were authorized by the author/s to evaluate the project or correct something on it, 
#    we assure you that all the rules above apply to your case as well, and so we advise deleting the entire project right after giving your feedback.  
#--> If you got this as a result of an unauthorized publishing we recommend that you permanently delete the entire project and report the unauthorized action to the author/s.
#-> Author: https://twitter.com/massellalou