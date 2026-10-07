<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use App\Models\Reservation;
use App\Models\ShiftDetail;
use App\Http\Requests\ReservationIndexRequest;

/**
 * 予約一覧画面のコントローラ
 */
class ReservationController extends Controller
{
    //web.phpのRoute::get('/')または('/?week=')から呼び出される。
    public function index(ReservationIndexRequest $request){
        // 表示する週の基準日（指定がなければ今日）
        $base  = $request->filled('week') ? Carbon::parse($request->week) : now();

        //上の行で実行したCarbonの時間を複製してから操作する
        //コピーしないと$baseが上書きされる
        $start  = $base->copy()->startOfWeek(Carbon::SUNDAY);   //週の日曜
        $end    = $base->copy()->endOfWeek(Carbon::SATURDAY);   //今週の土曜

        //予約の取得
        $reservations = Reservation::with([             //この列のデータを取得
                'client','supportType','vehicle','staffAssignments.staff'
            ])
            ->whereBetween('date', [$start, $end])      //date列で日曜日から土曜日だけに絞る
            ->orderBy('date')->orderBy('start_time')    //date列→start_time列で並べ替え
            ->get();                                    //データベースに問い合わせる

        //シフトの取得
        $shiftDetails = ShiftDetail::with('shift.staff')
            ->whereBetween('date', [$start, $end])
            ->get();

        return Inertia::render('Reservations/Index', [  //index.vueにデータを送る。
            'reservations' => $reservations,
            'shiftDetails' => $shiftDetails,
            'weekStart' => $start->toDateString(),
            'prevWeek' => $start->copy()->subWeek()->toDateString(),
            'nextWeek' => $start->copy()->addWeek()->toDateString(),
        ]);
    }
}
