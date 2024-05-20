<?php

namespace app\controller;

use core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $this->view('home', ['name' => 'Victor']);
    }

    public function teste()
    {
        $validator = new \core\Validator();
        $data = [
            'username' => 'victor',
        ];

        $rules = [
            'username' => 'required|unique:users,username',
        ];

        $validated = $validator->validate($data, $rules);

        if (!$validated) {
            return back();
        }
    }
}
