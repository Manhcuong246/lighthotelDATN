<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSqlCommand extends Command
{
    protected $signature = 'db:import {file : Đường dẫn file SQL}';

    protected $description = 'Import dữ liệu từ file SQL vào database';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! is_readable($file)) {
            $this->error("Không đọc được file: {$file}");
            return 1;
        }

        $sql = file_get_contents($file);
        if ($sql === false || $sql === '') {
            $this->error('File SQL trống hoặc không đọc được.');
            return 1;
        }

        $this->info('Đang import...');

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $this->dropTables();

            $statements = $this->splitSqlStatements($sql);
            $count = 0;
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '' || $this->isCommentOrEmpty($stmt)) {
                    continue;
                }
                try {
                    DB::statement($stmt);
                    $count++;
                } catch (\Throwable $e) {
                    if (! str_contains($e->getMessage(), 'Duplicate entry') && ! str_contains($e->getMessage(), 'already exists')) {
                        throw $e;
                    }
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->info("Import xong. Đã thực thi {$count} câu lệnh.");
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error('Lỗi: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function dropTables(): void
    {
        $tables = [
            'room_amenities', 'room_booked_dates', 'booking_logs', 'payments',
            'reviews', 'images', 'room_prices', 'bookings', 'rooms',
            'room_types', 'amenities', 'services', 'site_contents', 'coupons',
            'hotel_info', 'user_roles', 'roles', 'users',
            'cache', 'cache_locks', 'jobs', 'job_batches', 'migrations',
            'password_reset_tokens', 'sessions', 'failed_jobs', 'booking_services',
        ];

        foreach ($tables as $table) {
            try {
                DB::statement("DROP TABLE IF EXISTS `{$table}`");
            } catch (\Throwable) {
                // Bỏ qua nếu bảng không tồn tại
            }
        }
    }

    private function splitSqlStatements(string $sql): array
    {
        $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $statements = preg_split('/;\s*\n/', $sql, -1, PREG_SPLIT_NO_EMPTY);
        return array_map('trim', $statements);
    }

    private function isCommentOrEmpty(string $stmt): bool
    {
        $stmt = trim($stmt);
        return $stmt === ''
            || str_starts_with($stmt, '--')
            || str_starts_with($stmt, '/*!')
            || (str_starts_with($stmt, '/*') && str_ends_with($stmt, '*/'))
            || str_starts_with($stmt, 'SET @OLD_')
            || str_starts_with($stmt, 'SET CHARACTER_SET')
            || str_starts_with($stmt, 'COMMIT')
            || str_starts_with($stmt, 'START TRANSACTION');
    }
}
