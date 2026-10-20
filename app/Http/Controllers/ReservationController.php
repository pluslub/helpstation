<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use App\Models\Reservation;
use App\Models\ReservationStaff;
use App\Models\ShiftDetail;
use App\Models\Vehicle;
use App\Models\Client;
use App\Models\Staff;
use App\Models\SupportType;
use App\Http\Requests\ReservationIndexRequest;
use App\Http\Requests\ReservationMonthRequest;
use App\Http\Requests\ReservationStoreRequest;
use App\Http\Requests\ReservationUpdateRequest;
use App\Http\Requests\ReservationApproveRequest;
use App\Services\CurrentStaffService;
use Illuminate\Support\Facades\DB;

/**
 * 予約一覧画面のコントローラ
 */
class ReservationController extends Controller
{
    private function getReservations(Carbon $start, Carbon $end){
        return Reservation::with([             //この列のデータを取得
                    'client','supportType','vehicle','staffAssignments.staff'
                ])
                ->whereBetween('date', [$start, $end])      //date列で日曜日から土曜日だけに絞る
                ->orderBy('date')->orderBy('start_time')    //date列→start_time列で並べ替え
                ->get();                                    //データベースに問い合わせる
    }

    private function getShifts(Carbon $start, Carbon $end){
        return ShiftDetail::with('shift.staff')
                ->whereBetween('date', [$start, $end])
                ->get();                               //データベースに問い合わせる
    }

    private function getSundayList(Carbon $start, Carbon $end){
        $sundayList = [];

        $current = $start->copy();
        while($current <= $end){
            $sundayList[] = $current->toDateString();
            $current->addWeek();
        }
        return $sundayList;
    }

    private function getStaffRows(array $staffIds){
        //array_map(変換のしかた, 配列)
        //fn(引数) => (戻り値)
        $staffRows = array_map(fn($staffId) => ['staff_id' => $staffId], $staffIds);
        return $staffRows;
    }

    //web.phpのRoute::get('/')または('/?week=')から呼び出される。
    public function index(ReservationIndexRequest $request){
        // 表示する週の基準日（指定がなければ今日）
        $base  = $request->filled('week') ? Carbon::parse($request->week) : now();

        //上の行で実行したCarbonの時間を複製してから操作する
        //コピーしないと$baseが上書きされる
        $start  = $base->copy()->startOfWeek(Carbon::SUNDAY);   //週の日曜
        $end    = $base->copy()->endOfWeek(Carbon::SATURDAY);   //今週の土曜

        //予約の取得
        $reservations = $this->getReservations($start, $end);

        //シフトの取得
        $shiftDetails = $this->getShifts($start, $end);

        return Inertia::render('Reservations/Index', [  //index.vueにデータを送る。
            'reservations' => $reservations,
            'shiftDetails' => $shiftDetails,
            'weekStart' => $start->toDateString(),
            'prevWeek' => $start->copy()->subWeek()->toDateString(),
            'nextWeek' => $start->copy()->addWeek()->toDateString(),

            // 絞り込みの選択肢（論理削除されたものは SoftDeletes により自動で除外される）
            'vehicleOptions' => Vehicle::orderBy('id')->get(['id', 'name']),

            'formClients' => Client::orderBy('id')->get(['id', 'last_name']),
            'formStaff' => Staff::orderBy('id')->get(['id', 'name']),
            'formSupportTypes' => SupportType::orderBy('dispatch_priority')->get(['id', 'name']),
        ]);
    }

    //web.phpのRoute::get('/reservations/monthly')または('/reservations/monthly/?month=')から呼び出される。
    public function monthly(ReservationMonthRequest $request){
        // 表示する月の基準日（指定がなければ今日）
        $base  = $request->filled('month') ? Carbon::parse($request->month) : now();

        //上の行で実行したCarbonの時間を複製してから操作する
        //コピーしないと$baseが上書きされる
        $start  = $base->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);   //今月の最初の日曜
        $end    = $base->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);     //今月の最後の土曜

        //予約の取得
        $reservations = $this->getReservations($start, $end);

        //シフトの取得
        $shiftDetails = $this->getShifts($start, $end);

        return Inertia::render('Reservations/Monthly', [  //index.vueにデータを送る。
            'reservations' => $reservations,
            'shiftDetails' => $shiftDetails,
            'month' => $base->format('Y-m'),
            'prevMonth' => $base->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $base->copy()->addMonthNoOverflow()->format('Y-m'),
            'sundayList' => $this->getSundayList($start, $end),

            // 絞り込みの選択肢（論理削除されたものは SoftDeletes により自動で除外される）
            'vehicleOptions' => Vehicle::orderBy('id')->get(['id', 'name']),

            'formClients' => Client::orderBy('id')->get(['id', 'last_name']),
            'formStaff' => Staff::orderBy('id')->get(['id', 'name']),
            'formSupportTypes' => SupportType::orderBy('dispatch_priority')->get(['id', 'name']),
        ]);
    }

    // PHP配列について
    // https://www.php.net/manual/ja/function.array-map.php
    public function store(ReservationStoreRequest $request, CurrentStaffService $service){
        //予約担当者データを整形する。予約担当者はreservation_staffテーブルに保存する。
        //[1, 2]を[['staff_id' => 1], ['staff_id' => 2]]に変換する（未選択なら [['staff_id' => null]]）
        $validatedStaffIds = $request->validated()['staff_ids'] ?? [];
        $staffIds = $validatedStaffIds ? $validatedStaffIds : [null];

        $staffRows = $this->getStaffRows($staffIds);

        //予約担当者以外のデータはreservationモデルに保存する。
        $reservation = $request->safe()->except(['staff_ids']);

        //仮申請か承認済みか
        //管理者が登録した場合は直接承認済みにする。
        $loginStaff = $service->getLoginStaff();
        $status = $loginStaff?->role === 'admin' ? 'approved' : 'provisional';


        //予約データにフォームから送られないデータを追加する。
        //status, vehicle_id, vehicle_reassigned_flag
        $createValues = [
            'status' => $status,
            'vehicle_id' => null,
            'vehicle_reassigned_flag' => false
        ];
        $reservation = array_merge($reservation, $createValues);

        /**
         * 予約データと予約担当者データを一緒に登録する。
         * どちらかが失敗すると両方失敗にする。
         * https://readouble.com/laravel/13.x/ja/database.html
         *
         * function内から外部のデータを参照できないので、useで取り込む必要がある。
         */

        DB::transaction(function() use ($reservation, $staffRows){
            $reservationModel = Reservation::create($reservation);
            $reservationModel->staffAssignments()->createMany ($staffRows);
        });

        return back();
    }

    public function update(ReservationUpdateRequest $request, Reservation $reservation){
        $validatedStaffIds = $request->validated()['staff_ids'] ?? [];
        $staffIds = $validatedStaffIds ? $validatedStaffIds : [null];

        $staffRows = $this->getStaffRows($staffIds);

        //予約担当者以外のデータを配列に書き出す。
        $updateResArray = $request->safe()->except(['staff_ids']);

        //編集画面は管理者しかアクセスできないので承認済みにする。
        $updateValues = [
            'status' => 'approved',
        ];
        $updateResArray = array_merge($updateResArray, $updateValues);

        //車両の種類が変更されたら配車割り当てをクリアする⇒TODO:配車システムで割り当てしなおす
        if($updateResArray['vehicle_type_choice'] !== $reservation->vehicle_type_choice){
            $updateResArray['vehicle_id'] = null;
            $updateResArray['vehicle_reassigned_flag'] = false;
        }

        DB::transaction(function() use ($reservation, $updateResArray, $staffRows){
            $reservation->update($updateResArray);
            $reservation->staffAssignments()->delete();
            $reservation->staffAssignments()->createMany ($staffRows);
        });

        return back();
    }

    public function approve(ReservationApproveRequest $request, Reservation $reservation){
        //承認画面は管理者しかアクセスできないので承認済みにする。
        $approveValues = [
            'status' => 'approved',
        ];

        $reservation->update($approveValues);
        return back();
    }
}
