<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Check if email already exists
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Email is already registered.',
            ], 409); // 409 Conflict
        }

        try {
            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'user',
            ]);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token,
            ], 201);

        } catch (QueryException $e) {
            // Catch any DB error
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * Login with optional 2FA
     */
    public function login(Request $request)
    {
        $this->checkRateLimit($request, 'login');

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($this->throttleKey($request, 'login'));
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        RateLimiter::clear($this->throttleKey($request, 'login'));

        // Check if 2FA is enabled
        if (app_setting('enable_2fa')) {
            $otp = rand(100000, 999999);
            $user->update([
                'login_otp' => $otp,
                'login_otp_expires_at' => now()->addMinutes(5),
            ]);

            Mail::to($user->email)->send(new OtpMail($otp));

            return response()->json([
                'status' => true,
                'message' => 'OTP sent to your email. Please verify.',
                'requires_otp' => true,
            ]);
        }

        // 2FA disabled, return token immediately
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Verify OTP for login 2FA
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric',
        ]);

        $user = User::where('email', $request->email)
            ->where('login_otp', $request->otp)
            ->where('login_otp_expires_at', '>', now())
            ->first();

        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired OTP.',
            ], 400);
        }

        // Clear login OTP
        $user->update([
            'login_otp' => null,
            'login_otp_expires_at' => null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'OTP verified successfully',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        return response()->json([
            'status' => true,
            'user' => $request->user(),
        ]);
    }

    /**
     * Forgot password - send OTP
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }

        $otp = rand(100000, 999999);
        $user->update([
            'password_reset_otp' => $otp,
            'password_reset_expires_at' => now()->addMinutes(5),
        ]);

        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json([
            'status' => true,
            'message' => 'OTP sent to your email for password reset.',
        ]);
    }

    /**
     * Reset password using OTP
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)
            ->where('password_reset_otp', $request->otp)
            ->where('password_reset_expires_at', '>', now())
            ->first();

        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Invalid or expired OTP.'], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'password_reset_otp' => null,
            'password_reset_expires_at' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Password reset successful.',
        ]);
    }

    /**
     * Rate limit key generator
     */
    protected function throttleKey(Request $request, string $type = 'login')
    {
        return Str::lower($request->input('email')).'|'.$request->ip().'|'.$type;
    }

    /**
     * Check rate limit
     */
    protected function checkRateLimit(Request $request, string $type = 'login')
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request, $type), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($request, $type));
            return response()->json([
                'status' => false,
                'message' => "Too many attempts. Try again in {$seconds} seconds.",
            ], 429)->throwResponse();
        }
    }
}
