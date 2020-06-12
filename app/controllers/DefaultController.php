<?php

namespace App\Controllers;

use App\Core\Router;

class DefaultController
{
    protected $data;

    public function home()
    {
        Router::respond(1, 200, "need help ? head to the documentation page");
    }
}
