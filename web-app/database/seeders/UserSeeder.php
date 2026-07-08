<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'username'        => 'admin',
            'email'           => 'admin@smartforum.ac.ug',
            'password_hash'   => Hash::make('password'),
            'system_role'     => 'system_admin',
            'status'          => 'active',
            'agreed_to_rules' => true,
            'last_active_at'  => now(),
        ]);

        // Lecturers
        User::create([
            'username'        => 'dr_namukasa',
            'email'           => 'namukasa@smartforum.ac.ug',
            'password_hash'   => Hash::make('password'),
            'system_role'     => 'lecturer',
            'status'          => 'active',
            'agreed_to_rules' => true,
            'last_active_at'  => now()->subDays(1),
        ]);

        User::create([
            'username'        => 'prof_opio',
            'email'           => 'opio@smartforum.ac.ug',
            'password_hash'   => Hash::make('password'),
            'system_role'     => 'lecturer',
            'status'          => 'active',
            'agreed_to_rules' => true,
            'last_active_at'  => now()->subDays(2),
        ]);

        // Students
        $students = [
            ['kayongo_moses',  'moses@smartforum.ac.ug'],
            ['akello_sarah',   'sarah@smartforum.ac.ug'],
            ['opio_james',     'james@smartforum.ac.ug'],
            ['mugisha_dan',    'dan@smartforum.ac.ug'],
            ['nakato_alice',   'alice@smartforum.ac.ug'],
            ['ssemwanga_bob',  'bob@smartforum.ac.ug'],
            ['atim_grace',     'grace@smartforum.ac.ug'],
            ['okello_peter',   'peter@smartforum.ac.ug'],
        ];

        foreach ($students as [$username, $email]) {
            User::create([
                'username'        => $username,
                'email'           => $email,
                'password_hash'   => Hash::make('password'),
                'system_role'     => 'student',
                'status'          => 'active',
                'agreed_to_rules' => true,
                'last_active_at'  => now()->subDays(rand(0, 10)),
            ]);
        }

        // One blacklisted student for testing
        User::create([
            'username'        => 'inactive_user',
            'email'           => 'inactive@smartforum.ac.ug',
            'password_hash'   => Hash::make('password'),
            'system_role'     => 'student',
            'status'          => 'blacklisted',
            'agreed_to_rules' => true,
            'last_active_at'  => now()->subDays(60),
        ]);
    }
}
