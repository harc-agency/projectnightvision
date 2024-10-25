<?php

namespace App\Http\Controllers;

class DreamsController extends Controller
{
    public function index()
    {
        return inertia('Dreams/index', [
            'dreams' => 'tempvar',
        ]);

    }
}
