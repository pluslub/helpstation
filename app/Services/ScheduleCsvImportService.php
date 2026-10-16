<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Reservation;
use App\Models\Shift;
use App\Models\ShiftDetail;
use App\Models\Staff;
use App\Models\SupportType;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * 紙の予定表から作ったCSV（予約・シフト）を、テストサーバーのDBへ取り込む。
 *
 * 職員・利用者・支援内容がマスタに無い場合は、CSVに書かれた名前だけで自動登録する。
 * 同じCSVを何度取り込んでも二重登録にならないよう、既存のデータがあれば上書きする。
 *   - 予約：日付・開始時刻・利用者が同じ予約を同じ予約とみなす
 *   - シフト：日付・職員が同じシフト詳細を同じものとみなす
 */
class ScheduleCsvImportService
{
    public const RESERVATION_HEADERS = ['日付', '開始', '終了', '利用者', '担当者', '支援内容', '車両', '備考'];

    public const SHIFT_HEADERS = ['日付', '職員', '始業', '終業', '区分'];

    // 車両欄に書ける言葉 → reservation.vehicle_type_choice の値
    private const VEHICLE_CHOICES = [
        '福祉車両' => 'care_vehicle',
        '福祉' => 'care_vehicle',
        '普通社用車' => 'company_car',
        '社用車' => 'company_car',
        '普通車' => 'company_car',
        '普通' => 'company_car',
        '私用車' => 'private_car',
        '私有車' => 'private_car',
        '●' => 'private_car',
        '車不要' => 'none',
        'なし' => 'none',
    ];

    // シフトの区分 → [午前休, 午後休, 希望休]
    private const SHIFT_KINDS = [
        '' => [false, false, false],
        '勤務' => [false, false, false],
        '休' => [true, true, false],
        '希望休' => [true, true, true],
        '午前休' => [true, false, false],
        '午後休' => [false, true, false],
    ];

    // 担当者が決まっていないことを表す言葉
    private const UNDECIDED_STAFF = ['未定', '未決定'];

    // 名前 → モデル（同じ名前を何度も問い合わせないためのキャッシュ）
    private array $staffCache = [];

    private array $clientCache = [];

    private array $supportTypeCache = [];

    // 自動登録したマスタの名前（結果表示用）
    private array $createdMasters = ['職員' => [], '利用者' => [], '支援内容' => []];

    public function createdMasters(): array
    {
        return $this->createdMasters;
    }

    /**
     * 予約CSVを読み込んで検証する。DBには書き込まない。
     *
     * @return array{rows: array<int, array>, errors: string[]}
     */
    public function parseReservations(string $path): array
    {
        $rows = [];
        $errors = [];
        $keys = [];

        foreach ($this->readCsv($path, self::RESERVATION_HEADERS) as $line => $r) {
            $messages = [];
            $date = $this->parseDate($r['日付'], $messages);
            $start = $this->parseTime($r['開始'], '開始', $messages, true);
            $end = $this->parseTime($r['終了'], '終了', $messages, true);
            if ($start && $end && $start >= $end) {
                $messages[] = '終了は開始より後の時刻にしてください';
            }

            $client = $this->clean($r['利用者']);
            if ($client === '') {
                $messages[] = '利用者が空です';
            }

            $supportType = $this->clean($r['支援内容']);
            if ($supportType === '') {
                $messages[] = '支援内容が空です';
            }

            $vehicle = self::VEHICLE_CHOICES[$this->clean($r['車両'])] ?? null;
            if ($vehicle === null) {
                $messages[] = "車両は「福祉車両・普通社用車・私用車・車不要」のどれかにしてください（「{$r['車両']}」になっています）";
            }

            // 同じCSVの中に、日付・開始時刻・利用者が同じ予約が2行あると、後の行で上書きされてしまうため止める
            $key = "{$date}|{$start}|{$client}";
            if (! $messages && isset($keys[$key])) {
                $messages[] = "{$keys[$key]}行目と、日付・開始時刻・利用者が同じです";
            }

            if ($messages) {
                foreach ($messages as $message) {
                    $errors[] = "予約CSV {$line}行目：{$message}";
                }

                continue;
            }

            $keys[$key] = $line;
            $rows[] = [
                'date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'client' => $client,
                'staff' => $this->splitNames($r['担当者']),
                'support_type' => $supportType,
                'vehicle_type_choice' => $vehicle,
                'remarks' => $this->clean($r['備考']) ?: null,
            ];
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * シフトCSVを読み込んで検証する。DBには書き込まない。
     *
     * @return array{rows: array<int, array>, errors: string[]}
     */
    public function parseShifts(string $path): array
    {
        $rows = [];
        $errors = [];
        $keys = [];

        foreach ($this->readCsv($path, self::SHIFT_HEADERS) as $line => $r) {
            $messages = [];
            $date = $this->parseDate($r['日付'], $messages);

            $staff = $this->clean($r['職員']);
            if ($staff === '') {
                $messages[] = '職員が空です';
            }

            $kind = $this->clean($r['区分']);
            if (! array_key_exists($kind, self::SHIFT_KINDS)) {
                $messages[] = "区分は「空欄（勤務）・休・希望休・午前休・午後休」のどれかにしてください（「{$r['区分']}」になっています）";
            }

            // 終日の休みは時刻を使わない。それ以外は始業・終業を両方書く（午前休・午後休は空欄も可）
            $isDayOff = in_array($kind, ['休', '希望休'], true);
            $timeRequired = in_array($kind, ['', '勤務'], true);
            $start = $isDayOff ? null : $this->parseTime($r['始業'], '始業', $messages, $timeRequired);
            $end = $isDayOff ? null : $this->parseTime($r['終業'], '終業', $messages, $timeRequired);
            if (($start === null) !== ($end === null)) {
                $messages[] = '始業と終業は両方書くか、両方空欄にしてください';
            } elseif ($start && $end && $start >= $end) {
                $messages[] = '終業は始業より後の時刻にしてください';
            }

            $key = "{$date}|{$staff}";
            if (! $messages && isset($keys[$key])) {
                $messages[] = "{$keys[$key]}行目と、日付・職員が同じです";
            }

            if ($messages) {
                foreach ($messages as $message) {
                    $errors[] = "シフトCSV {$line}行目：{$message}";
                }

                continue;
            }

            $keys[$key] = $line;
            [$amOff, $pmOff, $desiredOff] = self::SHIFT_KINDS[$kind];
            $rows[] = [
                'date' => $date,
                'staff' => $staff,
                'start_time' => $start,
                'end_time' => $end,
                'am_off' => $amOff,
                'pm_off' => $pmOff,
                'desired_off' => $desiredOff,
            ];
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * 検証済みの予約を登録する。紙の予定表は確定した予定のため、承認済みとして登録する。
     * 具体的な車両の割り当て（vehicle_id）は、予約申請と同じく行わない。
     *
     * @return array{created: int, updated: int}
     */
    public function importReservations(array $rows): array
    {
        $count = ['created' => 0, 'updated' => 0];

        foreach ($rows as $row) {
            $client = $this->findOrCreateClient($row['client']);
            $reservation = Reservation::whereDate('date', $row['date'])
                ->where('start_time', $row['start_time'])
                ->where('client_id', $client->id)
                ->first() ?? new Reservation;
            $count[$reservation->exists ? 'updated' : 'created']++;

            $reservation->fill([
                'client_id' => $client->id,
                'date' => $row['date'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'support_type_id' => $this->findOrCreateSupportType($row['support_type'])->id,
                'vehicle_id' => null,
                'vehicle_type_choice' => $row['vehicle_type_choice'],
                'remarks' => $row['remarks'],
                'status' => 'approved',
                'vehicle_reassigned_flag' => false,
            ])->save();

            // 担当者は毎回入れ直す（担当者未定は staff_id が null の1行）
            $staffIds = array_map(fn ($name) => $this->findOrCreateStaff($name)->id, $row['staff']);
            $reservation->staffAssignments()->delete();
            $reservation->staffAssignments()->createMany(
                array_map(fn ($staffId) => ['staff_id' => $staffId], $staffIds ?: [null])
            );
        }

        return $count;
    }

    /**
     * 検証済みのシフトを、申請済み・承認済みとして登録する。
     *
     * @return array{created: int, updated: int}
     */
    public function importShifts(array $rows): array
    {
        $count = ['created' => 0, 'updated' => 0];

        foreach ($rows as $row) {
            $staff = $this->findOrCreateStaff($row['staff']);
            $shift = Shift::firstOrCreate(
                ['staff_id' => $staff->id, 'target_year_month' => substr($row['date'], 0, 7)],
                ['submitted_at' => now()],
            );

            $detail = ShiftDetail::where('shift_id', $shift->id)
                ->whereDate('date', $row['date'])
                ->first() ?? new ShiftDetail;
            $count[$detail->exists ? 'updated' : 'created']++;

            $detail->fill([
                'shift_id' => $shift->id,
                'date' => $row['date'],
                'applied_start_time' => $row['start_time'],
                'applied_end_time' => $row['end_time'],
                'applied_am_off' => $row['am_off'],
                'applied_pm_off' => $row['pm_off'],
                'applied_desired_off' => $row['desired_off'],
                'approval_flag' => true,
                'admin_modified_flag' => false,
                'modified_start_time' => null,
                'modified_end_time' => null,
                'modified_am_off' => false,
                'modified_pm_off' => false,
                'modified_approval_flag' => false,
            ])->save();
        }

        return $count;
    }

    /**
     * 職員を名前で探し、いなければ登録する。
     * ログインIDとパスワードは仮のものを付ける（パスワードは誰にも分からない値。使う場合は後で設定し直す）。
     */
    private function findOrCreateStaff(string $name): Staff
    {
        if (! isset($this->staffCache[$name])) {
            $staff = Staff::where('name', $name)->first();
            if (! $staff) {
                $staff = Staff::create([
                    'login_id' => 'import_'.Str::lower(Str::random(10)),
                    'password' => Hash::make(Str::random(40)),
                    'name' => $name,
                    'role' => 'staff',
                ]);
                $this->createdMasters['職員'][] = $name;
            }
            $this->staffCache[$name] = $staff;
        }

        return $this->staffCache[$name];
    }

    private function findOrCreateClient(string $lastName): Client
    {
        if (! isset($this->clientCache[$lastName])) {
            $client = Client::where('last_name', $lastName)->first();
            if (! $client) {
                $client = Client::create(['last_name' => $lastName, 'wheelchair_required' => false]);
                $this->createdMasters['利用者'][] = $lastName;
            }
            $this->clientCache[$lastName] = $client;
        }

        return $this->clientCache[$lastName];
    }

    /**
     * 支援内容を名前で探し、なければ登録する。配車優先度は既存のどれよりも低くする（値が大きいほど低い）。
     */
    private function findOrCreateSupportType(string $name): SupportType
    {
        if (! isset($this->supportTypeCache[$name])) {
            $supportType = SupportType::where('name', $name)->first();
            if (! $supportType) {
                $supportType = SupportType::create([
                    'name' => $name,
                    'dispatch_priority' => (SupportType::withTrashed()->max('dispatch_priority') ?? 0) + 1,
                ]);
                $this->createdMasters['支援内容'][] = $name;
            }
            $this->supportTypeCache[$name] = $supportType;
        }

        return $this->supportTypeCache[$name];
    }

    /**
     * CSVを1行ずつ「見出し => 値」の配列で返す。キーはファイル上の行番号。
     * Excelで保存したCSV（Shift_JIS、またはBOM付きUTF-8）も読めるようにする。
     */
    private function readCsv(string $path, array $requiredHeaders): \Generator
    {
        if (! is_file($path)) {
            throw new RuntimeException("ファイルが見つかりません：{$path}");
        }

        $text = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($path));
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'SJIS-win');
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $text);
        rewind($stream);

        $headers = array_map(fn ($h) => $this->clean((string) $h), fgetcsv($stream, escape: '') ?: []);
        $missing = array_diff($requiredHeaders, $headers);
        if ($missing) {
            throw new RuntimeException(basename($path).' の1行目に、次の見出しがありません：'.implode('・', $missing));
        }

        $line = 1;
        while (($cells = fgetcsv($stream, escape: '')) !== false) {
            $line++;
            // 空行は読み飛ばす
            if (implode('', array_map('trim', array_map('strval', $cells))) === '') {
                continue;
            }

            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = (string) ($cells[$i] ?? '');
            }

            yield $line => $row;
        }

        fclose($stream);
    }

    /**
     * 前後の空白を取り、全角の英数字・記号・空白を半角にする。
     */
    private function clean(string $value): string
    {
        return trim(mb_convert_kana($value, 'as'));
    }

    /**
     * 「2026/6/1」「2026-06-01」の形を「2026-06-01」にする。
     */
    private function parseDate(string $value, array &$messages): ?string
    {
        $value = $this->clean($value);
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $value, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }

        $messages[] = "日付は「2026/6/1」の形で書いてください（「{$value}」になっています）";

        return null;
    }

    /**
     * 「9:00」「09:00」の形を「09:00:00」にする。空欄は null。
     */
    private function parseTime(string $value, string $label, array &$messages, bool $required): ?string
    {
        $value = $this->clean($value);
        if ($value === '') {
            if ($required) {
                $messages[] = "{$label}が空です";
            }

            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m) && (int) $m[1] <= 23 && (int) $m[2] <= 59) {
            return sprintf('%02d:%02d:00', $m[1], $m[2]);
        }

        $messages[] = "{$label}は「9:00」の形で書いてください（「{$value}」になっています）";

        return null;
    }

    /**
     * 担当者欄を名前の配列にする。「・」「、」「/」や空白で区切って複数書ける。「未定」や空欄は担当者なし。
     */
    private function splitNames(string $value): array
    {
        $names = preg_split('/[・、,\/\s]+/u', $this->clean($value), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_diff($names, self::UNDECIDED_STAFF)));
    }
}
