<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $user = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin@gmail.com',
            'username' => 'superadmin',
            'password' => Hash::make('pass1234'),
            'ldap_bahagian' => 'Bahagian Pengurusan Maklumat'
        ]);
        $user->assignRole('superadmin');
    }
}
