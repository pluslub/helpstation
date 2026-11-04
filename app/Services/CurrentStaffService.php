<?php

namespace App\Services;

use App\Models\Staff;
use Illuminate\Support\Facades\Auth;

class CurrentStaffService
{
    public function getLoginStaff() : ?Staff{
        return Auth::user();
    }
}
