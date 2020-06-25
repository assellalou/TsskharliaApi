<?php
$router->get('', 'DefaultController@home');
$router->post('', 'DefaultController@home');
$router->get('author', 'DefaultController@author');
$router->post('login', 'UsersController@login');
$router->post('register', 'UsersController@register');
$router->post('profile', 'UsersController@profile');
$router->post('settings', 'UsersController@settings');
$router->post('check', 'UsersController@check');
$router->post('order/create', 'OrdersController@book');
$router->post('order/accept', 'OrdersController@match');
$router->post('order/bought', 'OrdersController@bought');
$router->post('order/delivered', 'OrdersController@delivered');
$router->post('openOrders', 'OrdersController@cityOrders');
$router->post('provider/items', 'OrdersController@getProviderItems');
$router->post('providers', 'UsersController@getProviders');
$router->post('charge', 'UsersController@charge');//demonstration only <--
