<?php

namespace App\Http\Controllers;
use App\Models\TimerRecord;
use Illuminate\Http\Request;

// web.phpで指定したURL入力されたときに実行される処理内容
class TimerController extends Controller
{
    // /timerへアクセスしたときに呼び出されるメソッド
    // 第1引数のtimerは、表示するページであるtimer.blade.phpを指し、
    // 第2引数以降のtime、recordsは引数として与える。
    public function show()
    {
        return view('timer', [
            'time' => now()->format('H:i:s'),

            // timer_recordsテーブルの全レコードをcreated_at、idでソートして取得
            'records' => TimerRecord::latest()->orderBy('id', 'desc')->get(),
        ]);
    }

    // 登録ボタンを押したとき
    public function store(Request $request)
    {
        // 入力チェック
        // elapsed_msは入力が必須で、整数で、最小値が0
        // エラーメッセージを出したいときはvalidateに送る第2引数に記述する。

        //     $request->validate([
        //         'elapsed_ms' => 'required|integer|min:0',
        //     ],
        //     [
        //         'elapsed_ms.required' => '経過時間を入力してください',
        //     ]);

        // https://qiita.com/gone0021/items/5699c29c7ce64f08b8d2

        $request->validate([
            'elapsed_ms' => 'required|integer|min:0',
        ]);

        // データベースへレコードの新規作成
        // TimerRecordModelのelapsed_msにフォームから送られたelapsed_msを入力する。
        TimerRecord::create([
            'elapsed_ms' => $request->elapsed_ms,
        ]);

        return response()->json(['status' => 'ok']);
    }

    // データベースからの削除
    // 今回はテーブル内のデータを全削除している。
    public function destroy()
    {
        TimerRecord::truncate();

        // ブラウザにjson形式でメッセージを送る。
        // 特に決まりはなさそうで、変数名 => 内容の形にすればなんでもおくれそう。
        // 第2変数にステータスコード
        // blade側でJSON.stringifyで受け取る。

        // return response()->json([
        //     'status' => 'error',
        //     'message' => 'アップロードされたファイルはCSVではありません。'
        // ], 400);
        return response()->json(['status' => 'ok']);
    }

    public function records()
    {
        return response()->json(TimerRecord::latest()->orderBy('id', 'desc')->get());
    }
}
