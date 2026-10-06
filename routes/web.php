<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TimerController;
use App\Http\Controllers\ReservationController;
use Inertia\Inertia;

// URL(/)が呼び出されたときにReservationControllerクラスのindexメソッドを実行する。
//
// name('reservations.index')
// ルートの命名　route('reservations.index')で呼び出せるようになる。
// ⇒   このページ以外は変更後のルートを指定していくことで、
//      このページのルートを変更すると全体が修正されコストを減らせる。
Route::get('/',[ReservationController::class, 'index'])->name('reservations.index');
