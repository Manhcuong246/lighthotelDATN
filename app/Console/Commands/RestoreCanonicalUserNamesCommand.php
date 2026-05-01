<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreCanonicalUserNamesCommand extends Command
{
    protected $signature = 'users:restore-canonical-names {--dry-run : Chỉ liệt kê, không ghi DB}';

    protected $description = 'Ghi đè users.full_name bằng bản chuẩn UTF-8 theo email (sửa chữ Việt vỡ do import sai charset)';

    public function handle(): int
    {
        $path = database_path('data/canonical_user_names_by_email.php');
        if (! is_file($path)) {
            $this->error('Thiếu file: '.$path);

            return self::FAILURE;
        }

        /** @var array<string, string> $map */
        $map = require $path;
        $dry = (bool) $this->option('dry-run');
        $updated = 0;

        foreach ($map as $email => $fullName) {
            $email = strtolower(trim((string) $email));
            $fullName = trim((string) $fullName);
            if ($email === '' || $fullName === '') {
                continue;
            }

            $row = DB::table('users')->whereRaw('LOWER(email) = ?', [$email])->first(['id', 'full_name']);
            if (! $row) {
                continue;
            }

            if ((string) $row->full_name === $fullName) {
                continue;
            }

            $this->line("#{$row->id} {$email}: «{$row->full_name}» → «{$fullName}»");
            $updated++;
            if (! $dry) {
                DB::table('users')->where('id', $row->id)->update(['full_name' => $fullName]);
            }
        }

        $this->info($dry
            ? "Dry-run: {$updated} bản ghi sẽ được cập nhật."
            : "Đã cập nhật {$updated} bản ghi users.full_name.");

        return self::SUCCESS;
    }
}
