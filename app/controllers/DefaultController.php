<?php

namespace App\Controllers;

use App\Core\Router;

class DefaultController
{
    protected $data;

    public function home()
    {
        Router::respond(1, 200, "You are authorized! if you don't know what to do please read the documentation.");
    }
}
