<?php

use app\model\User;

function route($name, $params = [])
{
  $url = '/' . ltrim($name, '/');
  if (!empty($params)) {
    $query = http_build_query($params);
    $url .= '?' . $query;
  }
  return $url;
}

function active($pattern, $active = 'active')
{
  $currentUri = $_SERVER['REQUEST_URI'];
  return preg_match("#^{$pattern}#", $currentUri) ? $active : '';
}

function url($path)
{
  $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
  return $baseUrl . '/' . ltrim($path, '/');
}

function asset($path)
{
  return url('assets/' . ltrim($path, '/'));
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

function redirect($url, $statusCode = 302)
{
  header('Location: ' . $url, true, $statusCode);
  exit();
}

function back()
{
  $url = $_SERVER['HTTP_REFERER'] ?? '/';
  redirect($url);
}

function view($view, $data = [])
{
  $view = new \core\View();
  $view->render($view, $data);
}

function env($key, $default = null)
{
  $value = getenv($key);
  return $value === false ? $default : $value;
}

function auth()
{
  return new class
  {
    public function loginById($id)
    {
      $user = new User();
      $data = $user->findOrFail($id);

      $token = md5($data->password . time() . rand(1, 999));

      $user->where('id', $data->id)->update(['session_token' => $token]);
      session()->set('session_token', $token);
    }

    public function user()
    {
      $user = new User();
      $token = session()->get('session_token');
      $user = $user->where('session_token', $token)->first();
      return $user ?? false;
    }

    public function login($email, $password)
    {
      $user = new User();
      $data = $user->where('email', $email)->first();

      if ($data && password_verify($password, $data->password)) {
        $token = md5($data->password . time() . rand(1, 999));

        $user->where('id', $data->id)->update(['session_token' => $token]);
        session()->set('session_token', $token);
        return redirect('/dashboard');
      }

      return back();
    }

    public function check()
    {
      if (!$this->user()) {
        return redirect('/');
      }
    }

    public function logout($redirect = '/')
    {
      session()->remove('session_token');
      redirect($redirect);
    }
  };
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

function error($key = '')
{
  $data = new class
  {
    public function has($key)
    {
      return session()->get('errors')[$key] ?? '';
    }

    public function show($key)
    {
      return session()->get('errors')[$key][0] ?? '';
    }

    public function clear()
    {
      if (isset($_SESSION['errors'])) {
        unset($_SESSION['errors']);
      }
    }
  };

  if ($key) {
    $data = $data->show($key);
  }

  return $data;
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

    public function clear()
    {
      if (isset($_SESSION['_old'])) {
        unset($_SESSION['_old']);
      }
    }
  };

  if (!empty($key)) {
    $data = $_SESSION['_old'][$key] ?? null;
    unset($_SESSION['_old'][$key]);
  }

  return $data;
}
