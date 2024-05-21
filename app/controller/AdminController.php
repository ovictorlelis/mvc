<?php

namespace app\controller;

use core\Controller;

class AdminController extends Controller
{
  public function __construct()
  {
    auth()->check();
  }

  public function index()
  {
    return $this->view('dashboard', ['title' => 'Dashboard']);
  }
}
