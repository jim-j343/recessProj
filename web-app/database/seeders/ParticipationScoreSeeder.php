<?php

namespace Database\Seeders;

use App\Models\GroupMembership;
use App\Models\ParticipationScore;
use App\Models\User;
use Illuminate\Database\Seeder;

class ParticipationScoreSeeder extends Seeder
{
    public function run(): void
    {
        $namukasa = User::where('username', 'dr_namukasa')->first();
        $opio     = User::where('username', 'prof_opio')->first();
        $ssali    = User::where('username', 'dr_ssali')->first();

        // [username, lecturer, score, remark, days ago]
        $entries = [
            ['kayongo_moses',  $namukasa, 78.5, 'Consistently helpful replies, especially on the SDLC thread. Keep it up.', 2],
            ['akello_sarah',   $namukasa, 91.0, 'Excellent engagement this week — thorough, well-reasoned answers.', 3],
            ['opio_james',     $namukasa, 42.0, 'Participation has dropped off. Please re-engage with the group discussions.', 1],
            ['mugisha_dan',    $opio,     67.5, 'Solid contributions to the MVC discussion.', 4],
            ['nakato_alice',   $opio,     85.0, 'Great work explaining OOP vs FP with real examples.', 2],
            ['ssemwanga_bob',  $opio,     73.0, 'Good grasp of normalization concepts.', 5],
            ['atim_grace',     $ssali,    88.0, 'Clear explanations on root-finding methods — helped several classmates.', 3],
            ['okello_peter',   $ssali,    55.0, 'A few good posts, but try to reply more consistently.', 6],
        ];

        foreach ($entries as [$username, $lecturer, $score, $remark, $daysAgo]) {
            $user = User::where('username', $username)->first();
            if (!$user || !$lecturer) {
                continue;
            }

            $groupId = GroupMembership::where('user_id', $user->user_id)->value('group_id');
            if (!$groupId) {
                continue;
            }

            $record = ParticipationScore::create([
                'user_id'    => $user->user_id,
                'group_id'   => $groupId,
                'criteria'   => $remark,
                'score'      => $score,
                'awarded_by' => $lecturer->user_id,
            ]);

            // created_at/updated_at aren't mass-assignable, so backdate
            // them after the fact for realistic "X days ago" timestamps
            $record->created_at = now()->subDays($daysAgo);
            $record->updated_at = now()->subDays($daysAgo);
            $record->save();
        }
    }
}
