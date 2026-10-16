<?php

namespace App\Console\Commands;

use App\Services\ScheduleCsvImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * 紙の予定表から作った予約・シフトのCSVを、テストサーバーのDBへ取り込むコマンド。
 * CSVには実際の利用者・職員の情報が入るため、Git管理外の storage/app/private/import/ に置く。
 * 書き方は環境構築手順書「4. 予定データの取り込み」を参照。
 *
 * 例：php artisan import:schedule --dry-run   （確認だけ）
 *     php artisan import:schedule             （取り込み）
 */
class ImportScheduleCsv extends Command
{
    protected $signature = 'import:schedule
        {--reservations= : 予約CSVのパス（省略時は storage/app/private/import/reservations.csv）}
        {--shifts= : シフトCSVのパス（省略時は storage/app/private/import/shifts.csv）}
        {--dry-run : 内容の確認だけ行い、DBには書き込まない}';

    protected $description = '紙の予定表から作った予約・シフトのCSVをDBに取り込む（テストサーバー用）';

    public function handle(ScheduleCsvImportService $service): int
    {
        if ($this->laravel->environment('production')) {
            $this->error('本番環境では実行できません（テストサーバー用のコマンドです）');

            return self::FAILURE;
        }

        try {
            $files = $this->targetFiles();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // まず全行を検証し、1行でも間違いがあれば何も書き込まずに終える
        try {
            $reservations = isset($files['reservations']) ? $service->parseReservations($files['reservations']) : ['rows' => [], 'errors' => []];
            $shifts = isset($files['shifts']) ? $service->parseShifts($files['shifts']) : ['rows' => [], 'errors' => []];
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $errors = [...$reservations['errors'], ...$shifts['errors']];
        if ($errors) {
            $this->error('CSVに直してほしい箇所があります。DBには何も書き込んでいません。');
            foreach ($errors as $error) {
                $this->line("  {$error}");
            }

            return self::FAILURE;
        }

        DB::beginTransaction();
        try {
            $reservationCount = $service->importReservations($reservations['rows']);
            $shiftCount = $service->importShifts($shifts['rows']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $dryRun = (bool) $this->option('dry-run');
        $dryRun ? DB::rollBack() : DB::commit();

        $this->info($dryRun ? '確認のみ（DBには書き込んでいません）' : '取り込みが終わりました');
        $this->table(['', '新規', '上書き'], [
            ['予約', $reservationCount['created'], $reservationCount['updated']],
            ['シフト（日ごと）', $shiftCount['created'], $shiftCount['updated']],
        ]);

        foreach ($service->createdMasters() as $label => $names) {
            if ($names) {
                $this->line("自動登録した{$label}（".count($names).'件）：'.implode('、', $names));
            }
        }

        return self::SUCCESS;
    }

    /**
     * 取り込むファイルを決める。オプションで指定したファイルが無ければエラー、
     * 省略時は既定の場所にあるファイルだけを対象にする。
     *
     * @return array{reservations?: string, shifts?: string}
     */
    private function targetFiles(): array
    {
        $files = [];
        foreach (['reservations', 'shifts'] as $kind) {
            $option = $this->option($kind);
            $path = $option ?? storage_path("app/private/import/{$kind}.csv");
            if (is_file($path)) {
                $files[$kind] = $path;
            } elseif ($option !== null) {
                throw new RuntimeException("ファイルが見つかりません：{$option}");
            }
        }

        if (! $files) {
            throw new RuntimeException('取り込むCSVがありません。storage/app/private/import/ に reservations.csv・shifts.csv を置いてください');
        }

        return $files;
    }
}
