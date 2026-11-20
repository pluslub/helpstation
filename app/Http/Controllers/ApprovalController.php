<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shift;

class ApprovalController extends Controller
{
    //管理者：職員全員・1か月分のシフト承認画面表示
    public function index(){
        abort(501);
    }

    //管理者：職員1人・1か月分のシフトを承認
    public function approve(Shift $shift){
        abort(501);
    }

    //管理者：職員複数人・1か月分のシフトをまとめて承認
    public function bulkApprove(){
        abort(501);
    }
}
