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


        // $user = User::create([
        //     'name' => 'jtk',
        //     'email' => 'jtk@gmail.com',
        //     'username' => 'jtk',
        //     'password' => Hash::make('pass1234'),
        //     'ldap_bahagian' => 'Bahagian Pengurusan Maklumat'
        // ]);
        // $user->assignRole('jtk');

        // $user = User::create([
        //     'name' => 'pptm',
        //     'email' => 'pptm@gmail.com',
        //     'username' => 'pptm',
        //     'password' => Hash::make('pass1234'),
        //     'ldap_bahagian' => 'Bahagian Pengurusan Maklumat'
        // ]);
        // $user->assignRole('pptm');

        // $user = User::create([
        //     'name' => 'ptm,',
        //     'email' => 'ptm@gmail.com',
        //     'username' => 'ptm',
        //     'password' => Hash::make('pass1234'),
        //     'ldap_bahagian' => 'Bahagian Pengurusan Maklumat'
        // ]);
        // $user->assignRole('ptm');

        // $user = User::create([
        //     'name' => 'admin,',
        //     'email' => 'admin@gmail.com',
        //     'username' => 'admin',
        //     'password' => Hash::make('pass1234'),
        //     'ldap_bahagian' => 'Bahagian Pengurusan Maklumat'
        // ]);
        // $user->assignRole('helpdesk.admin');

        // $user = User::create([
        //     'name' => 'farhan',
        //     'email' => 'farhan@gmail.com',
        //     'username' => 'farhan',
        //     'password' => Hash::make('pass1234'),
        //     'ldap_bahagian' => 'Bahagian Pengurusan Maklumat'
        // ]);
        // $user->assignRole('pengguna');
    }
}
