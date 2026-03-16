<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HotelInfo;

class FixBankIdSeeder extends Seeder
{
    public function run(): void
    {
        $hotelInfo = HotelInfo::first();
        if ($hotelInfo) {
            // Fix common bank ID typos
            $bankId = strtolower(trim($hotelInfo->bank_id));
            $corrections = [
                'mbbanh' => 'mbbank',
                'vietcombak' => 'vietcombank',
                'vietcomban' => 'vietcombank',
                'vcb' => 'vietcombank',
                'techcombak' => 'techcombank',
                'tpbanh' => 'tpbank',
            ];

            if (isset($corrections[$bankId])) {
                $hotelInfo->bank_id = $corrections[$bankId];
                $hotelInfo->save();
                echo "Fixed bank_id from '{$bankId}' to '{$corrections[$bankId]}'\n";
            } else {
                echo "Current bank_id: '{$bankId}' - no fix needed\n";
            }
        } else {
            echo "No hotel info found\n";
        }
    }
}
