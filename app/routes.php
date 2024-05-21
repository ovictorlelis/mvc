<?php

use core\Router;

Router::get('/', 'HomeController', 'index');

Router::get('/login', 'AuthController', 'login');
Router::post('/login', 'AuthController', 'authenticate');
Router::get('/register', 'AuthController', 'register');
Router::post('/register', 'AuthController', 'create');

Router::get('/logout', function () {
  auth()->logout();
});

Router::get('/dashboard', 'AdminController', 'index');
