<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // DB::table('users')->insert([
        //     'firmname' => 'demofirm',
        //     'firstname' => 'rahul',
        //     'lastname' => 'sharma',
        //     'email' => 'sharma@gmail.com',
        //     'phone' => '1234567890',
        //     'password' => Hash::make('12345678'),
        // ]);
    }
}
