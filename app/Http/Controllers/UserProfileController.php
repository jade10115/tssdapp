<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class UserProfileController extends Controller
{
    public function showProfile()
    {
        $user = Auth::user();
        $profile = UserProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json(['message' => 'User profile not found'], 404);
        }

        return response()->json($profile);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'profile_image' => 'nullable|string|max:255',
        ]);

        $profile = UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json([
            'message' => 'Profile saved successfully',
            'data' => $profile,
        ]);
    }

    public function getDivision()
    {
        $user = Auth::user();
        $profile = UserProfile::where('user_id', $user->id)->first();

        if (!$profile || !$profile->division) {
            return response()->json(['message' => 'No division found for this user'], 404);
        }

        return response()->json(['division' => $profile->division]);
    }

    public function getProfilesByDivision($division = null)
    {
        if (!$division) {
            $user = Auth::user();
            $userProfile = UserProfile::where('user_id', $user->id)->first();

            if (!$userProfile || !$userProfile->division) {
                return response()->json(['message' => 'No division found for this user'], 404);
            }

            $division = $userProfile->division;
        }

        $profiles = UserProfile::where('division', $division)->get();

        if ($profiles->isEmpty()) {
            return response()->json(['message' => 'No users found for this division'], 404);
        }

        return response()->json($profiles);
    }
public function signatoryList()
{
    $profiles = \App\Models\UserProfile::with(['position:id,position'])
        ->select('user_id', 'first_name', 'middle_name', 'last_name', 'suffix', 'division', 'position_id')
        ->whereNotNull('user_id')
        ->orderBy('first_name')
        ->get()
        ->map(function ($p) {
            $full = trim(preg_replace('/\s+/', ' ',
                ($p->first_name ?? '') . ' ' .
                ($p->middle_name ?? '') . ' ' .
                ($p->last_name ?? '') . ' ' .
                ($p->suffix ?? '')
            ));

            return [
                'user_id'   => $p->user_id,
                'full_name' => $full,
                // ✅ IMPORTANT: return a STRING position field Vue expects
                'position'  => $p->position?->position ?? '',
                'division'  => $p->division ?? '',
            ];
        })
        ->values();

    return response()->json($profiles);
}






}
