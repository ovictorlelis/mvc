<?php

if (!function_exists('route')) {
  /**
   * Generate a URL for the given route.
   *
   * @param string $name
   * @param array $params
   * @return string
   */
  function route($name, $params = [])
  {
    // Simples exemplo de geração de URL
    $url = '/' . ltrim($name, '/');
    if (!empty($params)) {
      $query = http_build_query($params);
      $url .= '?' . $query;
    }
    return $url;
  }
}

if (!function_exists('active')) {
  /**
   * Determine if the current route matches a given pattern.
   *
   * @param string $pattern
   * @return string
   */
  function active($pattern)
  {
    $currentUri = $_SERVER['REQUEST_URI'];
    return preg_match("#^{$pattern}#", $currentUri) ? 'active' : '';
  }
}

if (!function_exists('url')) {
  /**
   * Generate a full URL for the given path.
   *
   * @param string $path
   * @return string
   */
  function url($path)
  {
    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
    return $baseUrl . '/' . ltrim($path, '/');
  }
}

if (!function_exists('asset')) {
  /**
   * Generate a URL for an asset.
   *
   * @param string $path
   * @return string
   */
  function asset($path)
  {
    return url('assets/' . ltrim($path, '/'));
  }
}


function csrf_token()
{
  if (!session()->has('csrf_token')) {
    session()->set('csrf_token', bin2hex(random_bytes(32)));
  }

  return session()->get('csrf_token');
}

function csrf_field()
{
  return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function validateCsrfToken($token)
{
  if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
    throw new Exception('CSRF token inválido.');
  }
}

if (!function_exists('old')) {
  /**
   * Retrieve an old input value.
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  function old($key, $default = null)
  {
    return $_SESSION['old'][$key] ?? $default;
  }
}

if (!function_exists('redirect')) {
  /**
   * Redirect to a given URL.
   *
   * @param string $url
   * @param int $statusCode
   * @return void
   */
  function redirect($url, $statusCode = 302)
  {
    header('Location: ' . $url, true, $statusCode);
    exit();
  }
}

if (!function_exists('back')) {
  /**
   * Redirect to the previous URL.
   *
   * @return void
   */
  function back()
  {
    $url = $_SERVER['HTTP_REFERER'] ?? '/';
    redirect($url);
  }
}

if (!function_exists('view')) {
  /**
   * Render a view.
   *
   * @param string $view
   * @param array $data
   * @return void
   */
  function view($view, $data = [])
  {
    $view = new \core\View();
    $view->render($view, $data);
  }
}

if (!function_exists('env')) {
  /**
   * Get an environment variable.
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  function env($key, $default = null)
  {
    $value = getenv($key);
    return $value === false ? $default : $value;
  }
}

function session()
{
  return new class
  {
    public function start_if_needed()
    {
      if (session_status() === PHP_SESSION_NONE) {
        session_start();
      }
    }

    public function set($key, $value)
    {
      $this->start_if_needed();
      $_SESSION[$key] = $value;
    }

    public function get($key)
    {
      $this->start_if_needed();
      return $_SESSION[$key] ?? null;
    }

    public function show($key)
    {
      $this->start_if_needed();
      $session = $_SESSION[$key] ?? null;
      $this->remove($key);
      return $session;
    }

    public function remove($key)
    {
      $this->start_if_needed();
      if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
      }
    }

    public function has($key)
    {
      $this->start_if_needed();
      return isset($_SESSION[$key]);
    }
  };
}

function error()
{
  return new class
  {
    public function has($key)
    {
      return session()->get('errors')[$key] ?? '';
    }

    public function show($key)
    {
      return session()->show('errors')[$key][0] ?? '';
    }
  };
}

function old($key = '')
{
  $data = new class
  {
    public function set($key, $value)
    {
      if (!isset($_SESSION['_old'])) {
        $_SESSION['_old'] = [];
      }
      $_SESSION['_old'][$key] = $value;
    }

    public function get($key)
    {
      return $_SESSION['_old'][$key] ?? null;
    }

    public function has($key)
    {
      return isset($_SESSION['_old'][$key]);
    }

    public function remove($key)
    {
      if (isset($_SESSION['_old'][$key])) {
        unset($_SESSION['_old'][$key]);
      }
    }
  };

  if (!empty($key)) {
    $data = $_SESSION['_old'][$key] ?? null;
    unset($_SESSION['_old'][$key]);
  }

  return $data;
}
