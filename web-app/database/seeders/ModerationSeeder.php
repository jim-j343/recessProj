<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Blacklist;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\GroupRemoval;
use App\Models\Post;
use App\Models\PostReport;
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
                ?? Group::value('group_id');

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

        // ---- Group removal — a group admin removed a member, filing a
        // report for the system admin's Moderation queue ----
        $admin  = User::where('username', 'admin')->first();
        $numerical = Group::where('name', 'Numerical Methods Group')->first();
        $removedStudent = User::where('username', 'kato_isaac')->first();

        if ($admin && $numerical && $removedStudent) {
            GroupMembership::where('user_id', $removedStudent->user_id)
                ->where('group_id', $numerical->group_id)
                ->delete();

            $removal = GroupRemoval::create([
                'group_id'        => $numerical->group_id,
                'removed_user_id' => $removedStudent->user_id,
                'removed_by'      => $admin->user_id,
                'reason'          => 'Repeated off-topic posting after a warning.',
                'reviewed'        => false,
            ]);
            $removal->created_at = now()->subHours(6);
            $removal->save();

            ActivityLog::create([
                'user_id'     => $admin->user_id,
                'group_id'    => $numerical->group_id,
                'action_type' => 'member_removed',
                'meta'        => [
                    'removed_user_id'  => $removedStudent->user_id,
                    'removed_username' => $removedStudent->username,
                ],
                'logged_at'   => now()->subHours(6),
            ]);
        }

        // A second, already-reviewed removal for contrast in the queue
        $streamB = Group::where('name', 'BSSE Year 1 - Stream B')->first();
        $removedStudent2 = User::where('username', 'nabirye_ruth')->first();

        if ($admin && $streamB && $removedStudent2) {
            $stillMember = GroupMembership::where('user_id', $removedStudent2->user_id)
                ->where('group_id', $streamB->group_id)->exists();

            if ($stillMember) {
                $removal2 = GroupRemoval::create([
                    'group_id'        => $streamB->group_id,
                    'removed_user_id' => $removedStudent2->user_id,
                    'removed_by'      => $admin->user_id,
                    'reason'          => 'Requested to switch streams.',
                    'reviewed'        => true,
                    'reviewed_by'     => $admin->user_id,
                    'reviewed_at'     => now()->subDays(1),
                ]);
                $removal2->created_at = now()->subDays(2);
                $removal2->save();
            }
        }

        // ---- Post report — someone reported a post, sitting unreviewed
        // in the admin's Moderation queue ----
        $reporter = User::where('username', 'akello_sarah')->first();
        $flaggedPost = Post::where('is_flagged', true)->first();

        if ($reporter && $flaggedPost && $flaggedPost->author_id !== $reporter->user_id) {
            $report = PostReport::create([
                'post_id'     => $flaggedPost->post_id,
                'reported_by' => $reporter->user_id,
                'reason'      => 'This looks like spam, not a real question.',
                'reviewed'    => false,
            ]);
            $report->created_at = now()->subHours(3);
            $report->save();
        }
    }
}
