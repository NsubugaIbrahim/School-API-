<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Auth endpoints used by the Android app's LoginViewModel
 * (see LoginViewModel.kt -> RetrofitClient.apiService.login).
 *
 * Response shape MUST match data/model/Models.kt -> LoginResponse:
 *   { "token": "...", "user": { "id", "fullName", "email", "role", "profilePhotoUrl" } }
 *
 * "role" must be one of: ADMIN, TEACHER, BURSAR, STUDENT, PARENT
 * (see UserRole.fromString in Models.kt — it uppercases whatever
 * string is returned, so "Admin"/"admin"/"ADMIN" all work).
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid email or password',
            ], 422);
        }

        $user = User::where('email', $request->email)
            ->where('status', 'Active')
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'message' => 'Invalid email or password',
            ], 401);
        }

        // Revoke old tokens so each login issues a fresh one.
        $user->tokens()->delete();

        $token = $user->createToken('school-app')->plainTextToken;

        $user->update(['last_login' => now()]);

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $this->resolveDomainId($user),
                'fullName' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role->role_name ?? 'STUDENT',
                'profilePhotoUrl' => $user->profile_photo,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    /**
     * The mobile app's SessionManager stores a single "userId" and uses
     * it for both /api/students/{id}/... and /api/staff/{id}/... calls.
     *
     * To keep that simple, we return the *domain* id (students.student_id
     * for Student/Parent roles, staff.staff_id for Admin/Teacher/Bursar)
     * rather than users.user_id. This avoids a second lookup on every
     * subsequent request.
     */
    private function resolveDomainId(User $user): int
    {
        $roleName = $user->role->role_name ?? '';

        if (in_array($roleName, ['Student', 'Parent'], true)) {
            return $user->student?->student_id ?? $user->user_id;
        }

        return $user->staff?->staff_id ?? $user->user_id;
    }
}
