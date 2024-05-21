<?php

namespace app\controller;

use core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $this->view('welcome', ['title' => 'Welcome']);
    }
}
