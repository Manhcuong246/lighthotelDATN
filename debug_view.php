<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\View;

try {
    $booking = \App\Models\Booking::find(154);
    
    // Render view
    $html = View::make('admin.bookings._guests_by_room', [
        'booking' => $booking,
        'guestsByRoom' => []
    ])->render();
    
    echo "View rendered OK\n";
    echo "Length: " . strlen($html) . " chars\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Xem compiled content
    $compiledPath = storage_path('framework/views');
    $files = glob($compiledPath . '/*');
    $newest = null;
    $newestTime = 0;
    foreach ($files as $f) {
        $mtime = filemtime($f);
        if ($mtime > $newestTime) {
            $newestTime = $mtime;
            $newest = $f;
        }
    }
    if ($newest) {
        echo "\nLatest compiled file: $newest\n";
        $content = file_get_contents($newest);
        $lines = explode("\n", $content);
        for ($i = max(0, $e->getLine() - 5); $i < min(count($lines), $e->getLine() + 5); $i++) {
            echo ($i+1) . ": " . $lines[$i] . "\n";
        }
    }
}
