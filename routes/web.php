<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TimerController;
use Inertia\Inertia;

Route::get('/',fn() => Inertia::render('Reservations/Index'));
