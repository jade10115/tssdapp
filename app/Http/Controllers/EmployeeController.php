<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash; // ✅ NEW

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search  = trim((string) $request->get('search', ''));

        $q = UserProfile::query()
            ->leftJoin('users', 'users.id', '=', 'tbl_user_profile.user_id')
            ->leftJoin('tbl_position', 'tbl_position.id', '=', 'tbl_user_profile.position_id')
            ->select([
                'tbl_user_profile.user_id',
                'tbl_user_profile.first_name',
                'tbl_user_profile.middle_name',
                'tbl_user_profile.last_name',
                'tbl_user_profile.suffix',
                'tbl_user_profile.division',
                'tbl_user_profile.phone',
                'tbl_user_profile.address',
                'tbl_user_profile.profile_image',
                'tbl_user_profile.position_id',
                'tbl_position.position as position',
                'users.email',
                'users.userlevel_id', // ✅ include if you want it in frontend later
            ])
            ->orderBy('tbl_user_profile.id', 'desc');

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where(DB::raw("CONCAT(tbl_user_profile.first_name,' ',tbl_user_profile.last_name)"), 'like', "%{$search}%")
                    ->orWhere('tbl_user_profile.first_name', 'like', "%{$search}%")
                    ->orWhere('tbl_user_profile.last_name', 'like', "%{$search}%")
                    ->orWhere('tbl_user_profile.middle_name', 'like', "%{$search}%")
                    ->orWhere('tbl_user_profile.division', 'like', "%{$search}%")
                    ->orWhere('tbl_position.position', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        $employees = $q->paginate($perPage);

        $employees->getCollection()->transform(function ($row) {
            $full = trim(
                trim($row->first_name . ' ' . ($row->middle_name ? ($row->middle_name . ' ') : '') . $row->last_name) .
                ($row->suffix ? (' ' . $row->suffix) : '')
            );

            $row->full_name = $full ?: null;
            $row->profile_image_url = $row->profile_image
                ? url('userprofile/' . ltrim($row->profile_image, '/'))
                : null;

            return $row;
        });

        $divisionCounts = UserProfile::query()
            ->select('division', DB::raw('COUNT(*) as total'))
            ->groupBy('division')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json([
            'employees' => $employees,
            'division_counts' => $divisionCounts,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',

            'suffix'      => 'nullable|string|max:20',

            'division'    => 'required|string|max:255',
            'position_id' => 'required|integer|exists:tbl_position,id',
            'phone'       => 'nullable|string|max:255',
            'address'     => 'nullable|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:3',

            // ✅ NEW: userlevel
            'userlevel_id'   => 'required|integer|in:1,2,3',

            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        return DB::transaction(function () use ($request) {

            $user = User::create([
                'email'     => $request->email,
                'password'  => Hash::make($request->password), // ✅ HASHED
                'userlevel' => (int) $request->userlevel,      // ✅ SAVE TO users TABLE
            ]);

            $filename = null;
            if ($request->hasFile('profile_image')) {
                $file = $request->file('profile_image');
                $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();

                $dest = public_path('userprofile');
                if (!is_dir($dest)) {
                    @mkdir($dest, 0777, true);
                }
                $file->move($dest, $filename);
            }

            $suffix = $request->input('suffix');
            $suffix = is_null($suffix) ? '' : (string) $suffix;

            $profile = UserProfile::create([
                'user_id'       => $user->id,
                'first_name'    => $request->first_name,
                'middle_name'   => $request->middle_name,
                'last_name'     => $request->last_name,
                'suffix'        => $suffix,
                'division'      => $request->division,
                'position_id'   => $request->position_id,
                'phone'         => $request->phone,
                'address'       => $request->address,
                'profile_image' => $filename,
            ]);

            return response()->json([
                'message' => 'Employee created.',
                'user_id' => $user->id,
                'profile' => $profile,
            ], 201);
        });
    }

    public function update(Request $request, $userId)
    {
        $request->validate([
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',
            'suffix'      => 'nullable|string|max:20',

            'division'    => 'required|string|max:255',
            'position_id' => 'required|integer|exists:tbl_position,id',
            'phone'       => 'nullable|string|max:255',
            'address'     => 'nullable|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $userId,

            // ✅ optional update userlevel too (if you add it on edit modal later)
            'userlevel_id'   => 'nullable|integer|in:1,2,3',

            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        return DB::transaction(function () use ($request, $userId) {
            $user = User::findOrFail($userId);
            $profile = UserProfile::where('user_id', $userId)->firstOrFail();

            $userUpdate = ['email' => $request->email];

            if ($request->filled('userlevel')) {
                $userUpdate['userlevel'] = (int) $request->userlevel;
            }

            $user->update($userUpdate);

            if ($request->hasFile('profile_image')) {
                $file = $request->file('profile_image');
                $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();

                $dest = public_path('userprofile');
                if (!is_dir($dest)) {
                    @mkdir($dest, 0777, true);
                }
                $file->move($dest, $filename);

                if ($profile->profile_image) {
                    $old = public_path('userprofile/' . $profile->profile_image);
                    if (is_file($old)) @unlink($old);
                }

                $profile->profile_image = $filename;
            }

            $suffix = $request->input('suffix');
            $suffix = is_null($suffix) ? '' : (string) $suffix;

            $profile->first_name  = $request->first_name;
            $profile->middle_name = $request->middle_name;
            $profile->last_name   = $request->last_name;
            $profile->suffix      = $suffix;
            $profile->division    = $request->division;
            $profile->position_id = $request->position_id;
            $profile->phone       = $request->phone;
            $profile->address     = $request->address;
            $profile->save();

            return response()->json(['message' => 'Employee updated.']);
        });
    }

    public function destroy($userId)
    {
        return DB::transaction(function () use ($userId) {
            $profile = UserProfile::where('user_id', $userId)->first();

            if ($profile && $profile->profile_image) {
                $old = public_path('userprofile/' . $profile->profile_image);
                if (is_file($old)) @unlink($old);
            }

            if ($profile) $profile->delete();

            $user = User::find($userId);
            if ($user) $user->delete();

            return response()->json(['message' => 'Employee deleted.']);
        });
    }
}
