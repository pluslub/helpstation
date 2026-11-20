<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApprovalController;

// URL(/)が呼び出されたときにReservationControllerクラスのindexメソッドを実行する。
//
// name('reservations.index')
// ルートの命名　route('reservations.index')で呼び出せるようになる。
// ⇒   このページ以外は変更後のルートを指定していくことで、
//      このページのルートを変更すると全体が修正されコストを減らせる。

Route::get('/login',[AuthController::class, 'index'])->name('login')->middleware('guest');
Route::post('/login',[AuthController::class, 'login'])->name('login.send')->middleware(['guest', 'throttle:login']);

//ログインしてる場合だけ表示
Route::middleware('auth')->group(function () {
    /** ログイン機能 */
    Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

    /** 予約機能 */
    //職員：予約一覧（週）表示
    Route::get('/',[ReservationController::class, 'index'])->name('reservations.index');

    //職員：予約一覧（月）表示
    Route::get('/reservations/monthly',[ReservationController::class, 'monthly'])->name('reservations.monthly');

    //職員：予約新規追加
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');

    //管理者：予約編集
    Route::put('/reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');

    //管理者：予約承認
    Route::patch('/reservations/{reservation}/approve', [ReservationController::class, 'approve'])->name('reservations.approve');

    //管理者：予約削除
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');

    /** シフト機能 */
    //職員：1月分のシフト申請フォーム表示
    Route::get('/shifts/{yearMonth}', [ShiftController::class, 'index'])->name('shifts.index')->where('yearMonth', '\d{4}-\d{2}');

    //職員：入力したシフトを一時保存する
    Route::put('/shifts/{yearMonth}/draft', [ShiftController::class, 'draft'])->name('shifts.draft');

    //職員：入力したシフトを一括申請する
    Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');

    //管理者：職員1人・1日分のシフトを編集
    Route::put('/shift-details/{shiftDetail}', [ShiftController::class, 'updateDetail'])->name('shift-details.update');

    /** 承認機能 */
    //管理者：職員全員・1か月分のシフト承認画面表示
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');

    //管理者：職員1人・1か月分のシフトを承認
    Route::patch('/approvals/shifts/{shift}/approve', [ApprovalController::class, 'approve'])->name('approvals.shifts.approve');

    //管理者：職員複数人・1か月分のシフトをまとめて承認
    Route::patch('/approvals/shifts/approve', [ApprovalController::class, 'bulkApprove'])->name('approvals.shifts.bulk-approve');
});
