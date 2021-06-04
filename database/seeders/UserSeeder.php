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
            'password' => Hash::make('pass1234')
        ]);
        $user->assignRole('pengguna');

        $user = User::create([
            'name' => 'Farhan',
            'email' => 'farhan@gmail.com',
            'password' => Hash::make('pass1234')
        ]);
        $user->assignRole('helpdesk');

        $user = User::create([
            'name' => 'Hazim',
            'email' => 'hazim@gmail.com',
            'password' => Hash::make('pass1234')
        ]);
        $user->assignRole('jtk');

        $user = User::create([
            'name' => 'Syamil',
            'email' => 'syamil@gmail.com',
            'password' => Hash::make('pass1234')
        ]);
        $user->assignRole('pptm');

        $user = User::create([
            'name' => 'Syahmi',
            'email' => 'syahmi@gmail.com',
            'password' => Hash::make('pass1234')
        ]);
        $user->assignRole('ptm');

        $user = User::create([
            'name' => 'arham',
            'email' => 'arham@gmail.com',
            'password' => Hash::make('pass1234')
        ]);
        $user->assignRole('superadmin');



    }
}
