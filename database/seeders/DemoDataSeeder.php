<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientFixedDayOfWeek;
use App\Models\Reservation;
use App\Models\ReservationStaff;
use App\Models\Shift;
use App\Models\ShiftDetail;
use App\Models\Staff;
use App\Models\StaffDefaultSchedule;
use App\Models\SupportType;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 職員
        $okuno = Staff::create([
            'login_id' => 'okuno',
            'password' => Hash::make('password'),
            'name' => '奥野',
            'role' => 'admin',
        ]);

        $yamada = Staff::create([
            'login_id' => 'yamada',
            'password' => Hash::make('password'),
            'name' => '山田',
            'role' => 'staff',
        ]);

        $sato = Staff::create([
            'login_id' => 'sato',
            'password' => Hash::make('password'),
            'name' => '佐藤',
            'role' => 'staff',
        ]);

        $tanaka = Staff::create([
            'login_id' => 'tanaka',
            'password' => Hash::make('password'),
            'name' => '田中',
            'role' => 'staff',
        ]);

        // 職員デフォルト勤務時間（平日9-18時、土日休み）
        foreach ([$okuno, $yamada, $sato, $tanaka] as $staff) {
            foreach (range(0, 6) as $dayOfWeek) {
                $isWeekend = in_array($dayOfWeek, [0, 6]);
                StaffDefaultSchedule::create([
                    'staff_id' => $staff->id,
                    'day_of_week' => $dayOfWeek,
                    'is_am_off' => $isWeekend,
                    'is_pm_off' => $isWeekend,
                    'start_time' => $isWeekend ? null : '09:00',
                    'end_time' => $isWeekend ? null : '18:00',
                ]);
            }
        }

        // 利用者
        $suzuki = Client::create(['last_name' => '鈴木', 'wheelchair_required' => true]);
        $takahashi = Client::create(['last_name' => '高橋', 'wheelchair_required' => false]);
        $ito = Client::create(['last_name' => '伊藤', 'wheelchair_required' => false]);
        $watanabe = Client::create(['last_name' => '渡辺', 'wheelchair_required' => false, 'interval_unit' => 'month', 'interval_value' => 3]);
        $nakamura = Client::create(['last_name' => '中村', 'wheelchair_required' => true]);

        // 利用者デフォルト担当者（中間テーブル、モデルなし）
        DB::table('client_default_staff')->insert([
            ['client_id' => $suzuki->id, 'staff_id' => $yamada->id],
            ['client_id' => $suzuki->id, 'staff_id' => $sato->id],
            ['client_id' => $takahashi->id, 'staff_id' => $tanaka->id],
            ['client_id' => $ito->id, 'staff_id' => $yamada->id],
        ]);

        // 利用者固定訪問曜日（鈴木様：毎週水曜9:00〜10:00）
        ClientFixedDayOfWeek::create([
            'client_id' => $suzuki->id,
            'day_of_week' => 3,
            'fixed_start_time' => '09:00',
            'fixed_end_time' => '10:00',
        ]);

        // 支援内容・車両はSupportTypeSeeder・VehicleSeederで登録済みのものを参照
        $outpatientHospital = SupportType::where('name', '通院（病院）')->first();
        $homeSupport = SupportType::where('name', '居宅支援')->first();
        $wheelchairVehicle = Vehicle::where('type', 'care_ev')->first();
        $companyCar = Vehicle::where('type', 'company_car')->first();

        // シフト（今月分）＋シフト詳細（今週分のみ、サンプルのため）
        // $details[職員ID][曜日オフセット(0=日〜6=土)] に作成したシフト詳細を保持し、下のバリエーションで上書きする
        $targetYearMonth = now()->format('Y-m');
        $weekStart = now()->startOfWeek(Carbon::SUNDAY);
        $details = [];
        foreach ([$yamada, $sato, $tanaka] as $staff) {
            $shift = Shift::create([
                'staff_id' => $staff->id,
                'target_year_month' => $targetYearMonth,
                'submitted_at' => now(),
            ]);

            foreach (range(0, 6) as $offset) {
                $date = $weekStart->copy()->addDays($offset);
                $isWeekend = $date->isWeekend();
                $details[$staff->id][$offset] = ShiftDetail::create([
                    'shift_id' => $shift->id,
                    'date' => $date->toDateString(),
                    'applied_start_time' => $isWeekend ? null : '09:00',
                    'applied_end_time' => $isWeekend ? null : '18:00',
                    'applied_am_off' => $isWeekend,
                    'applied_pm_off' => $isWeekend,
                    'applied_desired_off' => false,
                    'approval_flag' => true,
                ]);
            }
        }

        // シフト詳細の表示確認用バリエーション（予約一覧画面のシフト・休み情報欄）
        // 月曜：佐藤 管理者が時間を変更（申請 9:00〜18:00 → 確定 10:00〜15:00）
        $details[$sato->id][1]->update([
            'admin_modified_flag' => true,
            'modified_start_time' => '10:00',
            'modified_end_time' => '15:00',
            'modified_approval_flag' => true,
        ]);

        // 月曜：田中 管理者が勤務を終日休みに変更（申請は勤務）
        $details[$tanaka->id][1]->update([
            'admin_modified_flag' => true,
            'modified_start_time' => null,
            'modified_end_time' => null,
            'modified_am_off' => true,
            'modified_pm_off' => true,
            'modified_approval_flag' => true,
        ]);

        // 火曜：田中 午前休（13:00〜18:00勤務）
        $details[$tanaka->id][2]->update([
            'applied_start_time' => '13:00',
            'applied_am_off' => true,
        ]);

        // 水曜：山田 午後休（9:00〜12:00勤務）
        $details[$yamada->id][3]->update([
            'applied_end_time' => '12:00',
            'applied_pm_off' => true,
        ]);

        // 木曜：佐藤 希望休（終日）
        $details[$sato->id][4]->update([
            'applied_start_time' => null,
            'applied_end_time' => null,
            'applied_am_off' => true,
            'applied_pm_off' => true,
            'applied_desired_off' => true,
        ]);

        // 金曜：田中 未承認（申請中）
        $details[$tanaka->id][5]->update([
            'approval_flag' => false,
        ]);

        // 予約（今日の日付で3件）
        $today = now()->toDateString();

        $r1 = Reservation::create([
            'client_id' => $suzuki->id,
            'date' => $today,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'support_type_id' => $outpatientHospital->id,
            'vehicle_id' => $wheelchairVehicle->id,
            'vehicle_type_choice' => 'care_vehicle',
            'status' => 'approved',
        ]);
        ReservationStaff::create(['reservation_id' => $r1->id, 'staff_id' => $yamada->id]);
        ReservationStaff::create(['reservation_id' => $r1->id, 'staff_id' => $sato->id]);

        $r2 = Reservation::create([
            'client_id' => $takahashi->id,
            'date' => $today,
            'start_time' => '10:30',
            'end_time' => '11:00',
            'support_type_id' => $homeSupport->id,
            'vehicle_id' => null,
            'vehicle_type_choice' => 'none',
            'status' => 'provisional',
        ]);
        ReservationStaff::create(['reservation_id' => $r2->id, 'staff_id' => $tanaka->id]);

        $r3 = Reservation::create([
            'client_id' => $ito->id,
            'date' => $today,
            'start_time' => '13:00',
            'end_time' => '14:00',
            'support_type_id' => $outpatientHospital->id,
            'vehicle_id' => $companyCar->id,
            'vehicle_type_choice' => 'company_car',
            'status' => 'approved',
            'vehicle_reassigned_flag' => true,
        ]);
        ReservationStaff::create(['reservation_id' => $r3->id, 'staff_id' => $yamada->id]);

        // ---------------------------------------------------------------
        // 予約一覧画面の表示確認用データ（週表示・カード表示・絞り込み・週切替）
        // ---------------------------------------------------------------

        // 絞り込みの選択肢に出てはいけないもの（論理削除済み）
        $retired = Staff::create([
            'login_id' => 'retired',
            'password' => Hash::make('password'),
            'name' => '退職者',
            'role' => 'staff',
        ]);
        $retired->delete();
        Vehicle::create(['name' => '廃車', 'type' => 'company_car'])->delete();

        // 論理削除済みの支援内容（予約カードで「削除済」表示の確認用）
        $oldSupport = SupportType::create(['name' => '旧支援', 'dispatch_priority' => 99]);
        $oldSupport->delete();

        // 車両の絞り込み確認用に、普通社用車をもう1台
        $companyCar2 = Vehicle::create(['name' => '普通車2', 'type' => 'company_car']);

        // 同じ苗字の利用者（絞り込みの選択肢で「鈴木（ID）」表示の確認用）
        $suzuki2 = Client::create(['last_name' => '鈴木', 'wheelchair_required' => false]);

        // 予約と担当者をまとめて作成する。担当者未定は staff_id に null を渡す
        $reserve = function (array $attributes, array $staffIds) {
            $reservation = Reservation::create($attributes + [
                'status' => 'approved',
                'vehicle_reassigned_flag' => false,
            ]);
            foreach ($staffIds as $staffId) {
                ReservationStaff::create(['reservation_id' => $reservation->id, 'staff_id' => $staffId]);
            }
            return $reservation;
        };
        // 週の起点（日曜）からの日数で日付を作る（負数で前週、7以上で次週）
        $dayOf = fn (int $offset) => $weekStart->copy()->addDays($offset)->toDateString();

        // 今週：日曜は予約なし（空の列の確認用）

        // 月曜：同じ苗字の利用者（鈴木2人目）、奥野担当（「自分自身」の確認用）、普通車2
        $reserve([
            'client_id' => $suzuki2->id, 'date' => $dayOf(1), 'start_time' => '10:00', 'end_time' => '11:00',
            'support_type_id' => $homeSupport->id, 'vehicle_id' => $companyCar2->id, 'vehicle_type_choice' => 'company_car',
        ], [$okuno->id]);

        // 火曜：仮登録かつ配車変更（バッジ2つ・枠の色の重なりの確認用）
        $reserve([
            'client_id' => $nakamura->id, 'date' => $dayOf(2), 'start_time' => '09:30', 'end_time' => '10:30',
            'support_type_id' => $outpatientHospital->id, 'vehicle_id' => $wheelchairVehicle->id, 'vehicle_type_choice' => 'care_vehicle',
            'status' => 'provisional', 'vehicle_reassigned_flag' => true,
        ], [$sato->id]);

        // 水曜：担当者未定の仮登録（「未定」表示の確認用）
        $reserve([
            'client_id' => $takahashi->id, 'date' => $dayOf(3), 'start_time' => '14:00', 'end_time' => '15:00',
            'support_type_id' => $homeSupport->id, 'vehicle_id' => null, 'vehicle_type_choice' => 'none',
            'status' => 'provisional',
        ], [null]);

        // 木曜：私用車（車両なし）、担当者2名（奥野・田中）
        $reserve([
            'client_id' => $ito->id, 'date' => $dayOf(4), 'start_time' => '11:00', 'end_time' => '12:00',
            'support_type_id' => $homeSupport->id, 'vehicle_id' => null, 'vehicle_type_choice' => 'private_car',
        ], [$okuno->id, $tanaka->id]);

        // 土曜：論理削除済みの支援内容（「削除済」表示の確認用）
        $reserve([
            'client_id' => $suzuki->id, 'date' => $dayOf(6), 'start_time' => '10:00', 'end_time' => '11:00',
            'support_type_id' => $oldSupport->id, 'vehicle_id' => $companyCar->id, 'vehicle_type_choice' => 'company_car',
        ], [$yamada->id]);

        // 今日：既存の3件より後に作成した早い時刻の予約（開始時刻順に並ぶかの確認用）
        $reserve([
            'client_id' => $ito->id, 'date' => $today, 'start_time' => '08:00', 'end_time' => '08:30',
            'support_type_id' => $homeSupport->id, 'vehicle_id' => $companyCar2->id, 'vehicle_type_choice' => 'company_car',
        ], [$tanaka->id]);

        // 次週：高橋の予約はなし（高橋で絞り込んだまま次週へ移ると0件になることの確認用）
        $reserve([
            'client_id' => $nakamura->id, 'date' => $dayOf(8), 'start_time' => '10:00', 'end_time' => '11:00',
            'support_type_id' => $outpatientHospital->id, 'vehicle_id' => $wheelchairVehicle->id, 'vehicle_type_choice' => 'care_vehicle',
        ], [$yamada->id]);
        $reserve([
            'client_id' => $suzuki->id, 'date' => $dayOf(10), 'start_time' => '09:00', 'end_time' => '10:00',
            'support_type_id' => $outpatientHospital->id, 'vehicle_id' => $wheelchairVehicle->id, 'vehicle_type_choice' => 'care_vehicle',
        ], [$sato->id]);

        // 前週
        $reserve([
            'client_id' => $ito->id, 'date' => $dayOf(-3), 'start_time' => '13:00', 'end_time' => '14:00',
            'support_type_id' => $outpatientHospital->id, 'vehicle_id' => $companyCar->id, 'vehicle_type_choice' => 'company_car',
        ], [$yamada->id]);

        // 渡辺はどの週にも予約なし（利用者の選択肢に出ないことの確認用）
    }
}
