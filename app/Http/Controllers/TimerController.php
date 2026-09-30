<?php

namespace App\Http\Controllers;
use App\Models\TimerRecord;
use Illuminate\Http\Request;

class TimerController extends Controller
{
    /*  /timerへアクセスしたときに呼び出されるメソッド
        第1引数のtimerは、表示するページであるtimer.blade.phpを指し、
        第2引数以降のtime、recordsは引数として与える。
    */
    public function show()
    {
        return view('timer', [
            'time' => now()->format('H:i:s'),

            //timer_recordsテーブルの全レコードをcreated_at、idでソートして取得
            'records' => TimerRecord::latest()->orderBy('id', 'desc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'elapsed_ms' => 'required|integer|min:0',
        ]);

        TimerRecord::create([
            'elapsed_ms' => $request->elapsed_ms,
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function destroy()
    {
        TimerRecord::truncate();

        return response()->json(['status' => 'ok']);
    }

    public function records()
    {
        return response()->json(TimerRecord::latest()->orderBy('id', 'desc')->get());
    }
}
