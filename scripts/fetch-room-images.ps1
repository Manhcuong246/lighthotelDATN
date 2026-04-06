# Tải ảnh mẫu vào storage/app/public/room_images/ và cập nhật DB.
# Yêu cầu: PHP, Composer, đã chạy php artisan storage:link
# Dùng --force để ghi đè ảnh local hiện có.

$ErrorActionPreference = "Stop"
$ProjectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $ProjectRoot

Write-Host ">> php artisan room-images:fetch" -ForegroundColor Cyan
php artisan room-images:fetch @args
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host "`n>> (Tùy chọn) Đồng bộ đường dẫn từ thư mục cũ rooms/ sang room_images/" -ForegroundColor DarkGray
Write-Host "   php artisan room-images:migrate-legacy" -ForegroundColor DarkGray
