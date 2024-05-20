<?php

use core\Router;

Router::get('/', 'HomeController', 'index');
Router::post('/', 'HomeController', 'teste');
