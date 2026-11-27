<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\ShiftDetail;
use App\Http\Requests\ShiftIndexRequest;
use App\Http\Requests\ShiftDetailUpdateRequest;
use App\Http\Requests\ShiftStoreRequest;
use App\Http\Requests\ShiftDraftRequest;

use App\Services\AuditLogger;

class ShiftController extends Controller
{
    //ログ出力のためのデータを作成する。
    private function getShiftDetailLog(ShiftDetail $shiftDetail){
        $logShiftDetailArray = $shiftDetail->only([ 'date',
                                                    'applied_start_time',
                                                    'applied_end_time',
                                                    'applied_am_off',
                                                    'applied_pm_off',
                                                    'modified_start_time',
                                                    'modified_end_time',
                                                    'modified_am_off',
                                                    'modified_pm_off',
                                                    'admin_modified_flag']);

        //日付2026-11-20 00:00:00　を　2026-11-20に直す
        $logShiftDetailArray['date'] = $logShiftDetailArray['date']->toDateString();
        return $logShiftDetailArray;
    }

    private function isDifferentFrom(array $validated, ShiftDetail $shiftDetail, string $prefix){
        $storedStartTime = $shiftDetail[$prefix.'start_time'] === null ? null : substr($shiftDetail[$prefix.'start_time'], 0, 5);
        $isDifferentStartTime = $storedStartTime !== $validated['start_time'];

        $storedEndTime = $shiftDetail[$prefix.'end_time'] === null ? null : substr($shiftDetail[$prefix.'end_time'], 0, 5);
        $isDifferentEndTime = $storedEndTime !== $validated['end_time'];

        $isDifferentAmOff = $shiftDetail[$prefix.'am_off'] !== (bool)$validated['am_off'];
        $isDifferentPmOff = $shiftDetail[$prefix.'pm_off'] !== (bool)$validated['pm_off'];
        return $isDifferentStartTime || $isDifferentEndTime || $isDifferentAmOff || $isDifferentPmOff;
    }

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
    //request.validated()：変更後、shiftDetail：変更前
    public function updateDetail(ShiftDetailUpdateRequest $request, ShiftDetail $shiftDetail, AuditLogger $auditLogger){
        $beforeLogDetailArray = $this->getShiftDetailLog($shiftDetail); //変更前ログ用文章取得
        $prefix = $shiftDetail['admin_modified_flag'] ? 'modified_' : 'applied_';

        $validated = $request->validated();
        if(!$this->isDifferentFrom($validated, $shiftDetail, $prefix)) return back();

        //元の入力と比較して一緒なら管理者変更を取り消す
        if(!$this->isDifferentFrom($validated, $shiftDetail, 'applied_')){
            $shiftDetail['modified_start_time'] = null;
            $shiftDetail['modified_end_time'] = null;
            $shiftDetail['modified_am_off'] = false;
            $shiftDetail['modified_pm_off'] = false;
            $shiftDetail['admin_modified_flag'] = false;
        }else{
            $shiftDetail['modified_start_time'] = $validated['start_time'];
            $shiftDetail['modified_end_time'] = $validated['end_time'];
            $shiftDetail['modified_am_off'] = (bool)$validated['am_off'];
            $shiftDetail['modified_pm_off'] = (bool)$validated['pm_off'];
            $shiftDetail['admin_modified_flag'] = true;
        }

        $shiftDetail->save();

        $afterLogDetailArray = $this->getShiftDetailLog($shiftDetail->fresh()); //変更後ログ用文章取得
        $auditLogger->log(action: 'update', staffId: Auth::id(), table: 'shift_detail', targetId: $shiftDetail->id, detail: ['before' => $beforeLogDetailArray, 'after' => $afterLogDetailArray], ip: $request->ip());
        return back();
    }
}
