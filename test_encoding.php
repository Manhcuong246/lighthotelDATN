<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rt = \App\Models\RoomType::find(1);
echo "DB text length: " . strlen($rt->description) . "\n";
echo "Text: " . substr($rt->description, 0, 100) . "\n";
