<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Telescope\LaravelAppCast;

class HomeController extends Controller
{
    /**
     * Display the Telescope view.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('telescope::layout', [
            'cssFile' => LaravelAppCast::$useDarkTheme ? 'app-dark.css' : 'app.css',
            'telescopeScriptVariables' => LaravelAppCast::scriptVariables(),
        ]);
    }
}
