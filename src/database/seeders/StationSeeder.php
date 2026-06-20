<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stations = [
            [
                'code' => 'ST001',
                'name' => '枚方観測所',
                'river_name' => '淀川',
                'prefecture' => '大阪府',
                'lat' => 34.8190000,
                'lng' => 135.6420000,
                'warning_level' => 4.50,
                'danger_level' => 5.50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ST002',
                'name' => '守口観測所',
                'river_name' => '淀川',
                'prefecture' => '大阪府',
                'lat' => 34.7350000,
                'lng' => 135.5620000,
                'warning_level' => 4.00,
                'danger_level' => 5.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ST003',
                'name' => '宇治観測所',
                'river_name' => '宇治川',
                'prefecture' => '京都府',
                'lat' => 34.8910000,
                'lng' => 135.8070000,
                'warning_level' => 2.50,
                'danger_level' => 3.50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ST004',
                'name' => '桂川観測所',
                'river_name' => '桂川',
                'prefecture' => '京都府',
                'lat' => 34.9780000,
                'lng' => 135.7020000,
                'warning_level' => 3.00,
                'danger_level' => 4.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ST005',
                'name' => '柏原観測所',
                'river_name' => '大和川',
                'prefecture' => '大阪府',
                'lat' => 34.5820000,
                'lng' => 135.6230000,
                'warning_level' => 3.50,
                'danger_level' => 4.80,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ST006',
                'name' => '王寺観測所',
                'river_name' => '大和川',
                'prefecture' => '奈良県',
                'lat' => 34.5980000,
                'lng' => 135.7020000,
                'warning_level' => 3.00,
                'danger_level' => 4.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ST007',
                'name' => '宝塚観測所',
                'river_name' => '武庫川',
                'prefecture' => '兵庫県',
                'lat' => 34.8080000,
                'lng' => 135.3420000,
                'warning_level' => 2.00,
                'danger_level' => 3.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ST008',
                'name' => '尼崎観測所',
                'river_name' => '武庫川',
                'prefecture' => '兵庫県',
                'lat' => 34.7370000,
                'lng' => 135.3780000,
                'warning_level' => 2.50,
                'danger_level' => 3.50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ST009',
                'name' => '五條観測所',
                'river_name' => '紀の川',
                'prefecture' => '奈良県',
                'lat' => 34.3480000,
                'lng' => 135.6980000,
                'warning_level' => 3.80,
                'danger_level' => 5.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ST010',
                'name' => '和歌山観測所',
                'river_name' => '紀の川',
                'prefecture' => '和歌山県',
                'lat' => 34.2320000,
                'lng' => 135.1920000,
                'warning_level' => 4.20,
                'danger_level' => 5.50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('stations')->upsert(
            $stations,
            ['code'],
            ['name', 'river_name', 'prefecture', 'lat', 'lng', 'warning_level', 'danger_level', 'updated_at']
        );
    }
}
