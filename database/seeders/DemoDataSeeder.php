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
        $targetYearMonth = now()->format('Y-m');
        foreach ([$yamada, $sato, $tanaka] as $staff) {
            $shift = Shift::create([
                'staff_id' => $staff->id,
                'target_year_month' => $targetYearMonth,
                'submitted_at' => now(),
            ]);

            foreach (range(0, 6) as $offset) {
                $date = now()->startOfWeek()->addDays($offset);
                $isWeekend = $date->isWeekend();
                ShiftDetail::create([
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
    }
}
