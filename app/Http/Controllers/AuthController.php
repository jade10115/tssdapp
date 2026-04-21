<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required','email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.'
            ], 401);
        }

        // ✅ SANCTUM TOKEN
        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        $profile = UserProfile::where('user_id', $user->id)->first();

        $fullName = $profile
            ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? ''))
            : '';

        $username = $fullName !== '' ? $fullName : $user->email;

        $profileImage = ($profile && $profile->profile_image)
            ? url('userprofile/' . $profile->profile_image)
            : url('userprofile/default.png');

        return response()->json([
            'success' => true,
            'message' => 'Login successful!',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'username' => $username,
                'userlevel_id' => (int)($user->userlevel_id ?? 0),

                // ✅ extra fields for sidebar
                'first_name' => $profile->first_name ?? '',
                'last_name' => $profile->last_name ?? '',
                'division' => $profile->division ?? '',
                'profile_image' => $profileImage,
            ]
        ]);
    }

    // ✅ Sidebar usually calls this to refresh user info
    public function session(Request $request)
    {
        $user = $request->user();

        $profile = UserProfile::where('user_id', $user->id)->first();

        $fullName = $profile
            ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? ''))
            : '';

        $username = $fullName !== '' ? $fullName : $user->email;

        $profileImage = ($profile && $profile->profile_image)
            ? url('userprofile/' . $profile->profile_image)
            : url('userprofile/default.png');

        return response()->json([
            'logged_in' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'username' => $username,
                'userlevel_id' => (int)($user->userlevel_id ?? 0),

                // ✅ extra fields for sidebar
                'first_name' => $profile->first_name ?? '',
                'last_name' => $profile->last_name ?? '',
                'division' => $profile->division ?? '',
                'profile_image' => $profileImage,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
