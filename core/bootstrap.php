<?php

core\Environment::load(dirname(__FILE__, 2));
date_default_timezone_set(getenv('APP_TIMEZONE'));

require_once 'helpers.php';
require_once '../app/routes.php';

\core\Router::handleRequest();
