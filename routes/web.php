<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 環境構築の動作確認用（PHP・Laravel・DB接続を確認したら削除してよい）
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Throwable $e) {
        $dbStatus = 'error: '.$e->getMessage();
    }

    return response()->json([
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'db_connection' => config('database.default'),
        'db_status' => $dbStatus,
        'time' => now()->toDateTimeString(),
    ]);
});
