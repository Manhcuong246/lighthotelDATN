<?php

namespace App\Console\Commands;

use App\Support\MojibakeUtf8Repair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairDatabaseUtf8MojibakeCommand extends Command
{
    protected $signature = 'db:repair-utf8-mojibake
                            {--dry-run : Chỉ liệt kê thay đổi, không UPDATE}
                            {--table= : Chỉ xử lý một bảng}
                            {--force : Thử mọi ô text không rỗng (chỉ ghi khi bản sửa được coi là tốt hơn)}
                            {--passes=2 : Chạy lặp N vòng (một vòng sửa xong có thể mở khóa thêm biến thể)}
                            {--chunk=250 : Số dòng mỗi lần đọc}';

    protected $description = 'Quét varchar/text toàn DB, sửa UTF-8/mojibake (heuristic mở rộng + cột name/title/… ). --dry-run trước. Kết hợp: php artisan users:restore-canonical-names';

    /** @var list<string> */
    private const SKIP_TABLES = [
        'migrations',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
    ];

    public function handle(): int
    {
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->error('Lệnh hiện chỉ hỗ trợ mysql/mariadb.');

            return self::FAILURE;
        }

        $dbName = DB::connection()->getDatabaseName();
        $dry = (bool) $this->option('dry-run');
        $onlyTable = $this->option('table') ? (string) $this->option('table') : null;
        $force = (bool) $this->option('force');
        $passes = max(1, min(10, (int) $this->option('passes')));
        $chunk = max(50, min(2000, (int) $this->option('chunk')));

        $textTypes = ['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext'];

        $cols = DB::select(
            'SELECT TABLE_NAME as tbl, COLUMN_NAME as col, DATA_TYPE as dtype
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND DATA_TYPE IN ('.implode(',', array_fill(0, count($textTypes), '?')).')
             ORDER BY TABLE_NAME, ORDINAL_POSITION',
            array_merge([$dbName], $textTypes)
        );

        $totalCells = 0;
        $totalUpdated = 0;

        foreach ($cols as $col) {
            $table = (string) $col->tbl;
            $column = (string) $col->col;

            if ($onlyTable !== null && strcasecmp($table, $onlyTable) !== 0) {
                continue;
            }

            if (in_array($table, self::SKIP_TABLES, true)) {
                continue;
            }

            if ($this->shouldSkipColumn($column)) {
                continue;
            }

            $pk = $this->primaryKeyColumns($dbName, $table);
            if ($pk === []) {
                $this->warn("Bỏ qua `{$table}`: không có PRIMARY KEY.");
                continue;
            }

            $select = array_unique(array_merge($pk, [$column]));
            $orderCol = $pk[0];

            $this->line("<fg=cyan>{$table}.{$column}</>");
            $cmd = $this;
            $effectivePasses = $dry ? 1 : $passes;

            for ($pass = 1; $pass <= $effectivePasses; $pass++) {
                $passUpdates = 0;

                DB::table($table)
                    ->select($select)
                    ->orderBy($orderCol)
                    ->chunk($chunk, function ($rows) use (
                        $table,
                        $column,
                        $pk,
                        $dry,
                        $force,
                        &$totalCells,
                        &$totalUpdated,
                        &$passUpdates,
                        $pass,
                        $cmd
                    ): void {
                        foreach ($rows as $row) {
                            $row = (array) $row;
                            $raw = $row[$column] ?? null;
                            if ($raw === null || $raw === '') {
                                continue;
                            }
                            $value = (string) $raw;
                            if ($pass === 1) {
                                $totalCells++;
                            }

                            $fixed = MojibakeUtf8Repair::repairForColumn($value, $column, $force);
                            if ($fixed === null || $fixed === $value) {
                                continue;
                            }

                            $previewOld = mb_substr($value, 0, 80);
                            $previewNew = mb_substr($fixed, 0, 80);
                            $cmd->line('  [p'.$pass.'] «'.$previewOld.'» → «'.$previewNew.'»');
                            $totalUpdated++;
                            $passUpdates++;

                            if (! $dry) {
                                $q = DB::table($table);
                                foreach ($pk as $k) {
                                    $q->where($k, $row[$k]);
                                }
                                $q->update([$column => $fixed]);
                            }
                        }
                    });

                if ($passUpdates === 0) {
                    break;
                }
            }

        }

        $this->newLine();
        $this->info($dry
            ? "Dry-run: {$totalUpdated} ô text sẽ được sửa (đã quét {$totalCells} ô không rỗng có khả năng liên quan)."
            : "Đã cập nhật {$totalUpdated} ô text (đã xét {$totalCells} ô không rỗng trong các cột được quét).");

        if ($dry && $totalUpdated > 0) {
            $this->comment('Chạy lại không có --dry-run để ghi DB.');
        }

        return self::SUCCESS;
    }

    private function shouldSkipColumn(string $column): bool
    {
        $lower = strtolower($column);

        return in_array($lower, ['password', 'remember_token'], true)
            || str_ends_with($lower, '_hash')
            || $lower === 'token';
    }

    /**
     * @return list<string>
     */
    private function primaryKeyColumns(string $database, string $table): array
    {
        $rows = DB::select(
            'SELECT COLUMN_NAME AS column_name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
             ORDER BY ORDINAL_POSITION',
            [$database, $table, 'PRIMARY']
        );

        $cols = [];
        foreach ($rows as $r) {
            $cols[] = (string) ($r->column_name ?? '');
        }

        return array_values(array_filter($cols, static fn (string $c) => $c !== ''));
    }
}
