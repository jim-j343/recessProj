<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileApiController extends Controller
{
    /**
     * PATCH/POST /api/profile
     * Update the authenticated user's username and/or profile picture.
     */
    public function update(Request $request)
    {
        $request->validate([
            'username' => ['nullable', 'string', 'max:255'],
            'avatar'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        if ($request->filled('username')) {
            $user->username = $request->username;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return response()->json([
            'message'     => 'Profile updated successfully.',
            'username'    => $user->username,
            'email'       => $user->email,
            'avatar'      => $user->avatar ? asset('storage/' . $user->avatar) : null,
            'avatar_path' => $user->avatar,
        ]);
    }

    /**
     * PATCH /api/profile/password
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->password_hash = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    /**
     * DELETE /api/profile
     * Delete the authenticated user's account.
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($request->filled('password')) {
            if (!Hash::check($request->password, $user->password_hash)) {
                throw ValidationException::withMessages([
                    'password' => ['The password is incorrect.'],
                ]);
            }
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully.',
        ]);
    }
}

