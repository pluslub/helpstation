<?php

namespace App\Http\Controllers;

class TimerController extends Controller
{
    public function show()
    {
        return view('timer', ['time' => now()->format('H:i:s')]);
    }
}
