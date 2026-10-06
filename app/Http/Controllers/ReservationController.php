<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Reservation;
use Illuminate\Support\Carbon;

/**
 * 予約一覧画面のコントローラ
 */
class ReservationController extends Controller
{
    //web.phpのRoute::get('/')から呼び出される。
    public function index(){
        $start  = now()->startOfWeek(Carbon::SUNDAY);   //今週の日曜
        $end    = now()->endOfWeek(Carbon::SATURDAY);   //今週の土曜

        $reservations = Reservation::with([             //この列のデータを取得
                'client','supportType','vehicle','staffAssignments.staff'
            ])
            ->whereBetween('date', [$start, $end])      //date列で日曜日から土曜日だけに絞る
            ->orderBy('date')->orderBy('start_time')    //date列→start_time列で並べ替え
            ->get();                                    //データベースに問い合わせる

        return Inertia::render('Reservations/Index', [  //index.vueにデータを送る。
            'reservations' => $reservations,
            'weekStart' => $start->toDateString(),
        ]);
    }
}
