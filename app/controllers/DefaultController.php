<?php

namespace App\Controllers;

use App\Core\{Router, App};

class DefaultController
{
    protected $data;

    public function home()
    {
        Router::respond(1, 200, 'OK', ["You are authorized! if you don't know what to do please read the documentation."]);
    }
    public function author()
    {
        Router::respond(1, 200, 'OK', [\base64_decode(App::get('config')['author'])]);
    }
}
