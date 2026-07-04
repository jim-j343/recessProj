<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::with('admin')
            ->withCount('memberships')
            ->withCount('topics')
            ->latest()
            ->get();

        return view('groups.index', compact('groups'));
    }

    public function create()
    {
        return view('groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:120', 'unique:groups,name'],
            'description'             => ['nullable', 'string', 'max:500'],
            'inactivity_warning_days' => ['required', 'integer', 'min:1'],
            'blacklist_duration_days' => ['required', 'integer', 'min:1'],
        ]);

        $group = Group::create([
            'admin_id'                => Auth::id(),
            'name'                    => $validated['name'],
            'description'             => $validated['description'] ?? null,
            'inactivity_warning_days' => $validated['inactivity_warning_days'],
            'blacklist_duration_days' => $validated['blacklist_duration_days'],
        ]);

        GroupMembership::create([
            'user_id'   => Auth::id(),
            'group_id'  => $group->group_id,
            'role'      => 'admin',
            'status'    => 'active',
            'joined_at' => now(),
        ]);

        return redirect()->route('groups.show', $group->group_id)
            ->with('success', 'Group created successfully!');
    }

    public function show(Group $group)
    {
        $group->load(['admin', 'topics.creator', 'memberships.user']);
        $isMember = $group->isMember(Auth::id());

        return view('groups.show', compact('group', 'isMember'));
    }

    public function join(Group $group)
    {
        if ($group->isMember(Auth::id())) {
            return back()->with('info', 'You are already a member.');
        }

        GroupMembership::create([
            'user_id'   => Auth::id(),
            'group_id'  => $group->group_id,
            'role'      => 'member',
            'status'    => 'active',
            'joined_at' => now(),
        ]);

        return back()->with('success', 'You joined ' . $group->name . '!');
    }

    public function leave(Group $group)
    {
        if ($group->admin_id === Auth::id()) {
            return back()->with('error', 'Group admin cannot leave.');
        }

        GroupMembership::where('user_id', Auth::id())
            ->where('group_id', $group->group_id)
            ->delete();

        return redirect()->route('groups.index')
            ->with('success', 'You left ' . $group->name . '.');
    }
}
