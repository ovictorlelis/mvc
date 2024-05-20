<?php

namespace core;

class Controller
{
    public function view($view, $data = [])
    {
        try {
            $render = new View();
            $render->render($view, $data);
        } catch (\Throwable $th) {
            echo "Erro: " . $th->getMessage();
        }
    }
}
