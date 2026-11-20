<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shift;
use App\Http\Requests\ApprovalIndexRequest;
use App\Http\Requests\ApprovalApproveRequest;
use App\Http\Requests\ApprovalBulkApproveRequest;

class ApprovalController extends Controller
{
    //管理者：職員全員・1か月分のシフト承認画面表示
    public function index(ApprovalIndexRequest $request){
        abort(501);
    }

    //管理者：職員1人・1か月分のシフトを承認
    public function approve(ApprovalApproveRequest $request, Shift $shift){
        abort(501);
    }

    //管理者：職員複数人・1か月分のシフトをまとめて承認
    public function bulkApprove(ApprovalBulkApproveRequest $request){
        abort(501);
    }
}
