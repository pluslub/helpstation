<?php

namespace App\Services;

use App\Models\Staff;

class CurrentStaffService
{
    // TODO: ログイン画面の実装後に Auth::user() へ置き換える（仮の認証）
    public function getLoginStaff() : ?Staff{
        return Staff::where('login_id', 'okuno')->first();
    }
}
