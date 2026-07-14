<?php

namespace App\Http\Controllers;

use App\Models\GroupInvitation;
use App\Models\GroupMembership;
use Illuminate\Support\Facades\Auth;

class GroupInvitationController extends Controller
{
    public function accept(GroupInvitation $invitation)
    {
        if ($invitation->invited_user_id !== Auth::id()) {
            abort(403);
        }

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'This invitation has already been responded to.');
        }

        // In case they joined some other way since the invite was sent
        $alreadyMember = GroupMembership::where('user_id', Auth::id())
            ->where('group_id', $invitation->group_id)
            ->exists();

        if (! $alreadyMember) {
            GroupMembership::create([
                'user_id'   => Auth::id(),
                'group_id'  => $invitation->group_id,
                'role'      => 'member',
                'status'    => 'active',
                'joined_at' => now(),
            ]);
        }

        $invitation->update([
            'status'       => 'accepted',
            'responded_at' => now(),
        ]);

        return redirect()->route('groups.show', $invitation->group_id)
            ->with('success', 'You joined ' . ($invitation->group->name ?? 'the group') . '!');
    }

    public function decline(GroupInvitation $invitation)
    {
        if ($invitation->invited_user_id !== Auth::id()) {
            abort(403);
        }

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'This invitation has already been responded to.');
        }

        $invitation->update([
            'status'       => 'declined',
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Invitation declined.');
    }
}
