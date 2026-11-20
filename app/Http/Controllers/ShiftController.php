<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShiftDetail;
use App\Http\Requests\ShiftIndexRequest;
use App\Http\Requests\ShiftDetailUpdateRequest;
use App\Http\Requests\ShiftStoreRequest;
use App\Http\Requests\ShiftDraftRequest;

class ShiftController extends Controller
{
    /** シフト機能 */
    //職員：1月分のシフト申請フォーム表示
    public function index(ShiftIndexRequest $request, string $yearMonth){
        abort(501);
    }

    //職員：入力したシフトを一時保存する
    public function draft(ShiftDraftRequest $request, string $yearMonth){
        abort(501);
    }

    //職員：入力したシフトを一括申請する
    public function store(ShiftStoreRequest $request){
        abort(501);
    }

    //管理者：職員1人・1日分のシフトを編集
    public function updateDetail(ShiftDetailUpdateRequest $request, ShiftDetail $shiftDetail){
        abort(501);
    }
}
