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
            'name' => 'Fatin',
            'email' => 'fatin@gmail.com',
            'username' => 'fatin',
            'password' => Hash::make('pass1234'),
            'ldap_bahagian' => 'Bahagian Pengurusan Maklumat'
        ]);
        $user->assignRole('pengguna');

        $user = User::create([
            'name' => 'Farhan',
            'email' => 'farhan@gmail.com',
            'username' => 'farhan',
            'password' => Hash::make('pass1234'),
            'ldap_bahagian' => 'Bahagian Pengurusan Maklumat'
        ]);
        $user->assignRole('helpdesk.admin');

        $user = User::create([
            'name' => 'Hazim',
            'email' => 'hazim@gmail.com',
            'username' => 'hazim',
            'password' => Hash::make('pass1234'),
            'ldap_bahagian' => 'Bahagian Pengurusan Maklumat'
        ]);
        $user->assignRole('jtk');

        $user = User::create([
            'name' => 'Syamil',
            'email' => 'syamil@gmail.com',
            'username' => 'syamil',
            'password' => Hash::make('pass1234'),
            'ldap_bahagian' => 'Bahagian Pengurusan Maklumat'
        ]);
        $user->assignRole('pptm');

        $user = User::create([
            'name' => 'Syahmi',
            'email' => 'syahmi@gmail.com',
            'username' => 'syahmi',
            'password' => Hash::make('pass1234'),
            'ldap_bahagian' => 'Bahagian Pengurusan Maklumat'
        ]);
        $user->assignRole('ptm');

        $user = User::create([
            'name' => 'arham',
            'email' => 'arham@gmail.com',
            'username' => 'arham',
            'password' => Hash::make('pass1234'),
            'ldap_bahagian' => 'Bahagian Pengurusan Maklumat'
        ]);
        $user->assignRole('superadmin');



    }
}
