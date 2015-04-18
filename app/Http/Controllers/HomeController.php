<?php

namespace App\Http\Controllers;


class HomeController extends Controller
{
    public function index() {
        $data = array();

        return view('index', $data);
    }
}
