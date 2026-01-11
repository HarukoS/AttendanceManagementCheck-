<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'user_id' => 2,
            'date' => '2025-12-22',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 2,
            'date' => '2025-12-23',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 2,
            'date' => '2025-12-24',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 2,
            'date' => '2025-12-25',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 2,
            'date' => '2025-12-26',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 2,
            'date' => '2026-01-05',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 2,
            'date' => '2026-01-06',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 2,
            'date' => '2026-01-07',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 2,
            'date' => '2026-01-08',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 2,
            'date' => '2026-01-09',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 3,
            'date' => '2025-12-22',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 3,
            'date' => '2025-12-23',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 3,
            'date' => '2025-12-24',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 3,
            'date' => '2025-12-25',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 3,
            'date' => '2025-12-26',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 3,
            'date' => '2026-01-05',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 3,
            'date' => '2026-01-06',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 3,
            'date' => '2026-01-07',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 3,
            'date' => '2026-01-08',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);

        $param = [
            'user_id' => 3,
            'date' => '2026-01-09',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'created_at' => now(),
        ];
        DB::table('works')->insert($param);
    }
}
