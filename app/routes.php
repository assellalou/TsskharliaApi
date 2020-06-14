<?php
$router->get('', 'DefaultController@home');
$router->post('', 'DefaultController@home');
$router->post('login', 'UsersController@login');
$router->post('register', 'UsersController@register');
$router->post('profile', 'UsersController@profile');
$router->post('settings', 'UsersController@settings');
$router->post('check', 'UsersController@check');
$router->post('order/create', 'OrdersController@book');
$router->post('order/accept', 'OrdersController@match');
$router->post('order/bought', 'OrdersController@bought');
