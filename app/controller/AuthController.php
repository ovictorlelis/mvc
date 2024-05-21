<?php

namespace app\controller;

use app\model\User;
use core\Controller;
use core\Validator;

class AuthController extends Controller
{
  public function login()
  {
    $this->view('auth/login');
  }

  public function authenticate()
  {
    $validator = new Validator();

    $data = filter_input_array(INPUT_POST);

    $rules = [
      'email' => 'required',
      'password' => 'required',
    ];

    $validated = $validator->validate($data, $rules);

    if (!$validated) {
      return back();
    }

    auth()->login($data['email'], $data['password']);
  }

  public function register()
  {
    $this->view('auth/register');
  }

  public function create()
  {
    $validator = new Validator();

    $data = filter_input_array(INPUT_POST);

    $rules = [
      'name' => 'required',
      'email' => 'required|unique:users,email',
      'password' => 'required',
    ];

    $validated = $validator->validate($data, $rules);

    if (!$validated) {
      return back();
    }

    $user = new User();
    $user = $user->insert([
      'name'      => $data['name'],
      'email'     => $data['email'],
      'password'  => password_hash($data['password'], PASSWORD_DEFAULT)
    ]);

    auth()->loginById($user);
    return redirect('/dashboard');
  }
}
