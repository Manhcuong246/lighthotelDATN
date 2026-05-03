<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApplyFixEncodingSqlCommand extends Command
{
    protected $signature = 'db:apply-fix-encoding
                            {--only= : Chỉ một file: fix_encoding | fix_encoding_2}
                            {--dry-run : Liệt kê file và số câu sẽ chạy, không ghi DB}';

    protected $description = 'Áp dụng database/fix_encoding.sql và fix_encoding_2.sql (chuẩn UTF-8 tiếng Việt Light Hotel; không DROP bảng).';

    public function handle(): int
    {
        $only = $this->option('only');
        $dry = (bool) $this->option('dry-run');

        $paths = match ($only ? (string) $only : null) {
            'fix_encoding' => [database_path('fix_encoding.sql')],
            'fix_encoding_2' => [database_path('fix_encoding_2.sql')],
            null, '', false => [
                database_path('fix_encoding.sql'),
                database_path('fix_encoding_2.sql'),
            ],
            default => null,
        };

        if ($paths === null) {
            $this->error('--only phải là fix_encoding hoặc fix_encoding_2.');

            return self::FAILURE;
        }

        foreach ($paths as $path) {
            if (! is_readable($path)) {
                $this->error("Không đọc được file: {$path}");

                return self::FAILURE;
            }
        }

        foreach ($paths as $path) {
            $sql = file_get_contents($path);
            if ($sql === false || $sql === '') {
                $this->error('File SQL trống hoặc lỗi đọc: '.basename($path));

                return self::FAILURE;
            }
            $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
            $stmts = array_values(array_filter(array_map(static fn (string $s): string => trim($s), $this->splitSqlStatements($sql))));
            $this->info(basename($path).': '.count($stmts).' câu lệnh');

            if ($dry) {
                continue;
            }

            foreach ($stmts as $stmt) {
                if ($this->isSkippableStatement($stmt)) {
                    continue;
                }
                DB::statement($stmt);
            }
        }

        if ($dry) {
            $this->comment('Dry-run: không ghi DB. Chạy lại không có --dry-run.');
        } else {
            $this->info('Đã áp dụng xong các bản fix UTF-8 trong SQL.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function splitSqlStatements(string $sql): array
    {
        $sql = preg_replace('/--[^\n]*\n/', "\n", $sql) ?? $sql;
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql) ?? $sql;
        $parts = preg_split('/;\s*\n/', $sql, -1, PREG_SPLIT_NO_EMPTY);

        return array_map(static fn (string $s): string => trim($s), $parts ?: []);
    }

    private function isSkippableStatement(string $stmt): bool
    {
        $t = trim($stmt);

        return $t === '' || str_starts_with($t, '--');
    }
}
