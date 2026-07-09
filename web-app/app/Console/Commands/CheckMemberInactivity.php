<?php

namespace App\Console\Commands;

use App\Models\Blacklist;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Warning;
use Illuminate\Console\Command;

class CheckMemberInactivity extends Command
{
    protected $signature = 'members:check-inactivity';

    protected $description = 'Warn, then blacklist, group members inactive beyond each group\'s configured thresholds (SDD 5.1 Member Inactivity & Blacklisting Component).';

    public function handle(): int
    {
        $warned1 = 0;
        $warned2 = 0;
        $blacklisted = 0;

        foreach (Group::all() as $group) {
            $threshold = max(1, (int) $group->inactivity_warning_days);

            $memberships = GroupMembership::where('group_id', $group->group_id)
                ->where('status', 'active')
                ->with('user')
                ->get();

            foreach ($memberships as $membership) {
                $member = $membership->user;
                if (! $member) {
                    continue;
                }

                $lastActive = $member->last_active_at ?? $member->created_at;
                if (! $lastActive) {
                    continue;
                }

                $daysInactive = $lastActive->diffInDays(now());

                $warningCount = Warning::where('user_id', $member->user_id)
                    ->where('group_id', $group->group_id)
                    ->count();

                if ($daysInactive >= $threshold * 3 && $warningCount >= 2) {
                    $stillBlacklisted = Blacklist::where('user_id', $member->user_id)
                        ->where('group_id', $group->group_id)
                        ->whereNull('lifted_by')
                        ->where(function ($q) {
                            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        })
                        ->exists();

                    if ($stillBlacklisted) {
                        continue;
                    }

                    Blacklist::create([
                        'user_id'        => $member->user_id,
                        'group_id'       => $group->group_id,
                        'reason'         => "Automatic: inactive for {$daysInactive} days",
                        'blacklisted_at' => now(),
                        'expires_at'     => now()->addDays(max(1, (int) $group->blacklist_duration_days)),
                    ]);

                    $membership->update(['status' => 'blacklisted']);
                    $member->update(['status' => 'blacklisted']);

                    $blacklisted++;
                    $this->line("Blacklisted {$member->username} in {$group->name} ({$daysInactive} days inactive).");
                } elseif ($daysInactive >= $threshold * 2 && $warningCount === 1) {
                    Warning::create([
                        'user_id'        => $member->user_id,
                        'group_id'       => $group->group_id,
                        'warning_number' => 2,
                        'issued_at'      => now(),
                        'deadline'       => now()->addDays($threshold),
                        'is_heeded'      => false,
                    ]);

                    $warned2++;
                    $this->line("Issued warning #2 to {$member->username} in {$group->name}.");
                } elseif ($daysInactive >= $threshold && $warningCount === 0) {
                    Warning::create([
                        'user_id'        => $member->user_id,
                        'group_id'       => $group->group_id,
                        'warning_number' => 1,
                        'issued_at'      => now(),
                        'deadline'       => now()->addDays($threshold),
                        'is_heeded'      => false,
                    ]);

                    $warned1++;
                    $this->line("Issued warning #1 to {$member->username} in {$group->name}.");
                }
            }
        }

        $this->info("Done. Warnings #1: {$warned1}, Warnings #2: {$warned2}, Blacklisted: {$blacklisted}.");

        return self::SUCCESS;
    }
}
