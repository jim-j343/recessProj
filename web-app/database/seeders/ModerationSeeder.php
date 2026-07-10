<?php

namespace Database\Seeders;

use App\Models\Blacklist;
use App\Models\GroupMembership;
use App\Models\User;
use App\Models\Warning;
use Illuminate\Database\Seeder;

class ModerationSeeder extends Seeder
{
    public function run(): void
    {
        // James gets one unheeded warning
        $james = User::where('username', 'opio_james')->first();
        if ($james) {
            $groupId = GroupMembership::where('user_id', $james->user_id)->value('group_id');
            Warning::create([
                'user_id'        => $james->user_id,
                'group_id'       => $groupId,
                'warning_number' => 1,
                'issued_at'      => now()->subDays(3),
                'deadline'       => now()->addDays(4),
                'is_heeded'      => false,
            ]);
        }

        // The inactive user gets 2 warnings + an active blacklist
        $inactive = User::where('username', 'inactive_user')->first();
        if ($inactive) {
            $groupId = GroupMembership::where('user_id', $inactive->user_id)->value('group_id')
                ?? \App\Models\Group::value('group_id');

            // Make sure they have a membership so blacklist has group context
            if (!GroupMembership::where('user_id', $inactive->user_id)->exists()) {
                GroupMembership::create([
                    'user_id'   => $inactive->user_id,
                    'group_id'  => $groupId,
                    'role'      => 'member',
                    'status'    => 'blacklisted',
                    'joined_at' => now()->subDays(70),
                ]);
            }

            foreach ([1, 2] as $n) {
                Warning::create([
                    'user_id'        => $inactive->user_id,
                    'group_id'       => $groupId,
                    'warning_number' => $n,
                    'issued_at'      => now()->subDays(30 - $n * 7),
                    'deadline'       => now()->subDays(23 - $n * 7),
                    'is_heeded'      => false,
                ]);
            }

            Blacklist::create([
                'user_id'        => $inactive->user_id,
                'group_id'       => $groupId,
                'reason'         => 'Ignored 2 inactivity warnings',
                'blacklisted_at' => now()->subDays(9),
                'expires_at'     => now()->addDays(21),
            ]);
        }
    }
}
