<?php

namespace core;

class Router
{
    protected static $routes = [];

    public static function addRoute($method, $path, $controller, $action = null)
    {
        self::$routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    public static function handleRequest()
    {
        $currentMethod = $_SERVER['REQUEST_METHOD'];
        $currentUri = strtok($_SERVER['REQUEST_URI'], '?');

        foreach (self::$routes as $route) {
            if ($currentMethod !== $route['method']) {
                continue;
            }

            $pattern = preg_replace('#\{[^\}]+\}#', '([^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#siD';

            if (preg_match($pattern, $currentUri, $matches)) {
                array_shift($matches);

                if ($currentMethod === 'POST') {
                    if (!isset($_POST['csrf_token'])) {
                        throw new \Exception('CSRF token não encontrado.');
                    }

                    validateCsrfToken($_POST['csrf_token']);

                    self::storePostData();
                }

                if (is_callable($route['controller'])) {
                    call_user_func_array($route['controller'], $matches);
                } else {
                    $controller = "\\app\\controller\\{$route['controller']}";
                    $controllerInstance = new $controller();
                    call_user_func_array([$controllerInstance, $route['action']], $matches);
                }

                return;
            }
        }

        self::handleNotFound();
    }

    protected static function handleNotFound()
    {
        http_response_code(404);
        $controller = new \core\Controller();
        $controller->view('404');
    }

    public static function get($path, $controller, $action = null)
    {
        self::addRoute('GET', $path, $controller, $action);
    }

    public static function post($path, $controller, $action = null)
    {
        self::addRoute('POST', $path, $controller, $action);
    }

    public static function put($path, $controller, $action = null)
    {
        self::addRoute('PUT', $path, $controller, $action);
    }

    public static function patch($path, $controller, $action = null)
    {
        self::addRoute('PATCH', $path, $controller, $action);
    }

    public static function delete($path, $controller, $action = null)
    {
        self::addRoute('DELETE', $path, $controller, $action);
    }

    protected static function storePostData()
    {
        foreach ($_POST as $key => $value) {
            old()->set($key, $value);
        }
    }
}
