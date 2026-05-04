<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BRAND = 'Light Hotel';

    /** Các chuỗi tên cũ thường gặp — thay bằng tên thương hiệu chuẩn. */
    private const LEGACY_NAMES = [
        'Grand Vista Resort Đà Nẵng',
        'Grand Vista Resort',
        'Grand Vista',
    ];

    public function up(): void
    {
        if (Schema::hasTable('hotel_info')) {
            DB::table('hotel_info')->update(['name' => self::BRAND]);
        }

        if (Schema::hasTable('site_contents')) {
            $rows = DB::table('site_contents')->get();
            foreach ($rows as $row) {
                $title = $row->title;
                $content = $row->content;
                foreach (self::LEGACY_NAMES as $legacy) {
                    if (is_string($title) && $title !== '') {
                        $title = str_ireplace($legacy, self::BRAND, $title);
                    }
                    if (is_string($content) && $content !== '') {
                        $content = str_ireplace($legacy, self::BRAND, $content);
                    }
                }
                if ($title !== $row->title || $content !== $row->content) {
                    DB::table('site_contents')->where('id', $row->id)->update([
                        'title' => $title,
                        'content' => $content,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
    }
};
