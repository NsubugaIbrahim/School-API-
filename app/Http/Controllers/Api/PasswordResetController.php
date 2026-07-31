<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Send a 6-digit reset code to the user's email.
     */
    public function sendResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'We couldn\'t find a user with that email address.',
            ], 422);
        }

        $email = $request->email;
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(60);

        // Store the code in the database
        DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($code),
                'expires_at' => $expiresAt,
                'created_at' => Carbon::now(),
            ]
        );

        // TODO: In a real app, send the code via email.
        // For now, we'll return it in the response for development purposes.

        return response()->json([
            'message' => 'Reset code sent to your email.',
            'dev_code' => $code // REMOVE THIS IN PRODUCTION
        ]);
    }

    /**
     * Reset the user's password using the 6-digit code.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$reset || Carbon::parse($reset->expires_at)->isPast()) {
            return response()->json([
                'message' => 'The reset code is invalid or has expired.',
            ], 422);
        }

        if (!Hash::check($request->code, $reset->token)) {
            return response()->json([
                'message' => 'The reset code is incorrect.',
            ], 422);
        }

        // Update the user's password
        $user = User::where('email', $request->email)->first();
        $user->password_hash = Hash::make($request->password);
        $user->save();

        // Delete the reset record
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Your password has been reset successfully.',
        ]);
    }
}
