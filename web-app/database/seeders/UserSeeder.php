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

        // Lecturers — one per course cluster, so admin analytics'
        // Lecturer Performance table has more than two rows to show
        $lecturers = [
            ['dr_namukasa', 'namukasa@smartforum.ac.ug'],
            ['prof_opio',   'opio@smartforum.ac.ug'],
            ['dr_ssali',    'ssali@smartforum.ac.ug'],
        ];

        foreach ($lecturers as $i => [$username, $email]) {
            User::create([
                'username'        => $username,
                'email'           => $email,
                'password_hash'   => Hash::make('password'),
                'system_role'     => 'lecturer',
                'status'          => 'active',
                'agreed_to_rules' => true,
                'last_active_at'  => now()->subDays($i + 1),
            ]);
        }

        // Students — enough to populate 6 groups meaningfully, most
        // enrolled across several courses at once (a real BSSE Year 1
        // student's actual semester course load)
        $students = [
            'kayongo_moses',  'akello_sarah',  'opio_james',   'mugisha_dan',
            'nakato_alice',   'ssemwanga_bob', 'atim_grace',   'okello_peter',
            'nabirye_ruth',   'kato_isaac',
        ];

        foreach ($students as $username) {
            User::create([
                'username'        => $username,
                'email'           => str_replace('_', '.', $username) . '@smartforum.ac.ug',
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
