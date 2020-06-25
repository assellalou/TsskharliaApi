<?php

namespace App\Core;


class Router
{
    public $routes = [
        'GET' => [],
        'POST' => []
    ];

    public static function load($file)
    {
        $router = new static;
        require $file;
        return $router;
    }

    public function get($uri, $controller)
    {
        $this->routes['GET'][$uri] = $controller;
    }

    public function post($uri, $controller)
    {
        $this->routes['POST'][$uri] = $controller;
    }

    public function direct($uri, $requestType)
    {
        if (array_key_exists($uri, $this->routes[$requestType])) {
            return $this->callAction(
                ...explode('@', $this->routes[$requestType][$uri])
            );
        }
        self::respond(0, 404, 'Not Found!');
    }

    private function callAction($controller, $action)
    {
        $controller = "App\\Controllers\\{$controller}";
        $controller = new $controller;
        if (!method_exists($controller, $action)) {
            self::respond(0, 404, 'Not Found!');
        }
        return $controller->$action();
    }

    public static function respond($success, $status, $message, $extras = [])
    {
        Log::URITracker($status, $message);
        echo json_encode(
            [
                'success' => $success,
                'status' => $status,
                'message' => $message,
                'data' => $extras
            ]
        );
    }
}
