<?php

/**
 * Kiểm tra gửi mail SMTP (ví dụ Gmail): php scripts/smoke-mail.php
 * Gửi tới MAIL_FROM_ADDRESS (self-test).
 */

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$to = (string) config('mail.from.address');
if ($to === '' || strcasecmp($to, 'hello@example.com') === 0) {
    fwrite(STDERR, "Thiếu MAIL_FROM_ADDRESS hợp lệ trong .env.\n");
    exit(1);
}

try {
    Illuminate\Support\Facades\Mail::raw('Light Hotel — kiểm tra SMTP.', function ($m) use ($to) {
        $m->to($to)->subject('Light Hotel — smoke mail');
    });
    echo "OK — đã gọi gửi tới {$to}. Kiểm tra hộp thư (và spam).\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERR: '.$e->getMessage()."\n");
    exit(1);
}
