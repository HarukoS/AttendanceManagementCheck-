<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'id' => 1,
            'name' => '管理者',
            'email' => 'admin@gmail.com',
            'email_verified_at' => now(),
            'role' => 'admin',
            'created_at' => now(),
            'password' => bcrypt('password'),
        ];
        DB::table('users')->insert($param);

        $param = [
            'id' => 2,
            'name' => '鈴木太郎',
            'email' => 'user1@gmail.com',
            'email_verified_at' => now(),
            'role' => 'user',
            'created_at' => now(),
            'password' => bcrypt('password'),
        ];
        DB::table('users')->insert($param);

        $param = [
            'id' => 3,
            'name' => '鈴木花子',
            'email' => 'user2@gmail.com',
            'email_verified_at' => now(),
            'role' => 'user',
            'created_at' => now(),
            'password' => bcrypt('password'),
        ];
        DB::table('users')->insert($param);
    }
}
