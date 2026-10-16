<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Reservation;
use App\Models\ReservationStaff;
use App\Models\ShiftDetail;
use App\Models\Staff;
use App\Models\SupportType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportScheduleCsvTest extends TestCase
{
    use RefreshDatabase;

    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    private function csv(string $content, string $encoding = 'UTF-8'): string
    {
        $base = tempnam(sys_get_temp_dir(), 'import');
        $path = $base.'.csv';
        file_put_contents($path, $encoding === 'UTF-8' ? $content : mb_convert_encoding($content, $encoding, 'UTF-8'));
        array_push($this->tempFiles, $base, $path);

        return $path;
    }

    private function reservationCsv(): string
    {
        return $this->csv(
            "日付,開始,終了,利用者,担当者,支援内容,車両,備考\n".
            "2026/6/1,8:45,9:15,小林,室長,居宅,福祉車両,\n".
            "2026/6/1,9:30,14:00,山田,幸野・林,同行（買い物）,私用車,昼食持参\n".
            "2026/6/2,10:00,11:00,小林,未定,通院（病院）,車不要,10:30迎え\n"
        );
    }

    private function shiftCsv(): string
    {
        return $this->csv(
            "日付,職員,始業,終業,区分\n".
            "2026/6/1,室長,9:30,18:15,\n".
            "2026/6/1,幸野,,,休\n".
            "2026/6/1,林,,,希望休\n".
            "2026/6/2,林,8:30,12:30,午後休\n"
        );
    }

    public function test_予約とシフトを取り込み、無いマスタは自動登録する(): void
    {
        $this->artisan('import:schedule', [
            '--reservations' => $this->reservationCsv(),
            '--shifts' => $this->shiftCsv(),
        ])->assertExitCode(0);

        $this->assertSame(3, Reservation::count());
        $this->assertEqualsCanonicalizing(['室長', '幸野', '林'], Staff::pluck('name')->all());
        $this->assertEqualsCanonicalizing(['小林', '山田'], Client::pluck('last_name')->all());
        $this->assertSame(3, SupportType::count());

        $shopping = Reservation::where('remarks', '昼食持参')->first();
        $this->assertSame('approved', $shopping->status);
        $this->assertSame('private_car', $shopping->vehicle_type_choice);
        $this->assertNull($shopping->vehicle_id);
        $this->assertEqualsCanonicalizing(['幸野', '林'], $shopping->staffAssignments()->with('staff')->get()->pluck('staff.name')->all());

        // 担当者「未定」は staff_id が null の1行
        $undecided = Reservation::where('remarks', '10:30迎え')->first();
        $this->assertSame([null], $undecided->staffAssignments()->pluck('staff_id')->all());

        $this->assertSame(4, ShiftDetail::count());
        $desiredOff = ShiftDetail::whereHas('shift.staff', fn ($q) => $q->where('name', '林'))->whereDate('date', '2026-06-01')->first();
        $this->assertTrue($desiredOff->applied_am_off && $desiredOff->applied_pm_off && $desiredOff->applied_desired_off);
        $this->assertTrue($desiredOff->approval_flag);
    }

    public function test_同じCSVを2回取り込んでも二重登録にならない(): void
    {
        $reservations = $this->reservationCsv();
        $shifts = $this->shiftCsv();

        $this->artisan('import:schedule', ['--reservations' => $reservations, '--shifts' => $shifts])->assertExitCode(0);
        $this->artisan('import:schedule', ['--reservations' => $reservations, '--shifts' => $shifts])->assertExitCode(0);

        $this->assertSame(3, Reservation::count());
        $this->assertSame(4, ReservationStaff::count());
        $this->assertSame(3, Staff::count());
        $this->assertSame(4, ShiftDetail::count());
    }

    public function test_間違いが1行でもあれば何も書き込まない(): void
    {
        $path = $this->csv(
            "日付,開始,終了,利用者,担当者,支援内容,車両,備考\n".
            "2026/6/1,8:45,9:15,小林,室長,居宅,福祉車両,\n".
            "2026/6/1,10:00,9:00,山田,林,居宅,軽トラ,\n"
        );

        $this->artisan('import:schedule', ['--reservations' => $path])
            ->expectsOutputToContain('予約CSV 3行目：終了は開始より後の時刻にしてください')
            ->expectsOutputToContain('予約CSV 3行目：車両は')
            ->assertExitCode(1);

        $this->assertSame(0, Reservation::count());
        $this->assertSame(0, Staff::count());
    }

    public function test_dry_runではDBに書き込まない(): void
    {
        $this->artisan('import:schedule', [
            '--reservations' => $this->reservationCsv(),
            '--shifts' => $this->shiftCsv(),
            '--dry-run' => true,
        ])->expectsOutputToContain('確認のみ')->assertExitCode(0);

        $this->assertSame(0, Reservation::count());
        $this->assertSame(0, ShiftDetail::count());
        $this->assertSame(0, Staff::count());
    }

    public function test_ExcelのShift_JISのCSVも読める(): void
    {
        $path = $this->csv(
            "日付,開始,終了,利用者,担当者,支援内容,車両,備考\r\n".
            "２０２６/６/１,９：００,１０：００,鈴木,室長,居宅,車不要,\r\n",
            'SJIS-win'
        );

        $this->artisan('import:schedule', ['--reservations' => $path])->assertExitCode(0);

        $reservation = Reservation::first();
        $this->assertSame('鈴木', $reservation->client->last_name);
        $this->assertSame('2026-06-01', $reservation->date->format('Y-m-d'));
        $this->assertStringStartsWith('09:00', $reservation->start_time);
    }
}
