<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 作成の流れ
// 1.  テーブル定義用のファイルを生成
//     php artisan make:migration create_timer_records_table
// 2.  テーブルの構造を入力
//     database/migrations/配下に日付付きのファイルが生成されるので、up()メソッドの中のSchema::create部分に、必要なカラムを追加
// 3.  テーブルを作成
//     php artisan migrate
// 4.  モデルを作る
//     php artisan make:model TimerRecord
class TimerRecord extends Model
{
    // protected $fillable = ['elapsed_ms']; 入力に対して指定された列だけに値を入力する。ホワイトリスト。
    // protected $guarded  = ['elapsed_ms']; 入力に対して指定された列だけ入力を禁止する。ブラックリスト。
    protected $fillable = ['elapsed_ms'];
}
