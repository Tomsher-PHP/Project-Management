<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetOtpMail;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\User;
use App\Services\UserLoginSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function __construct(private readonly UserLoginSessionService $loginSessionService) {}

    private function getPostLoginRoute(): string
    {
        $user = Auth::user();

        if ($user && ($user->is_super_admin || $user->can('dashboard.view'))) {
            return route('dashboard');
        }

        return route('user.workspace');
    }

    public function showLogin()
    {
        // redirect to dashboard if already logged
        if (Auth::check()) {
            return redirect()->to($this->getPostLoginRoute());
        }
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect('/dashboard');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        // is_active should be true to allow login
        $credentials['is_active'] = true;

        // Attempt login
        if (! Auth::attempt($credentials, $remember)) {
            return back()->with('error', 'Invalid credentials');
        }

        // Prevent session fixation
        $request->session()->regenerate();

        $this->loginSessionService->recordLogin(Auth::user(), $request);

        return redirect()->intended($this->getPostLoginRoute())
            ->with('success', 'Welcome back!');
    }

    // Send otp to forgot password mail
    public function sendOtp(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        $existingOtpExpiresAt = $user->password_otp_expires_at
            ? Carbon::parse($user->password_otp_expires_at)
            : null;

        if ($existingOtpExpiresAt && now()->lt($existingOtpExpiresAt->copy()->subMinutes(4))) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait 60 seconds before requesting a new OTP.',
                'email' => $user->email,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $isResendRequest = !empty($user->password_otp) && $existingOtpExpiresAt && now()->lte($existingOtpExpiresAt);

        $otp = random_int(10000, 99999);

        try {
            $user->update([
                'password_otp' => $otp,
                'password_otp_expires_at' => now()->addMinutes(5),
            ]);

            Mail::to($user->email)->send(new PasswordResetOtpMail($otp, $user));

            return response()->json([
                'success' => true,
                'message' => $isResendRequest ? 'OTP resent successfully' : 'OTP sent successfully',
                'email' => $user->email,
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Password reset OTP mail failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send OTP email at the moment. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (
            !$user ||
            $user->password_otp !== $request->otp
            /** Hash::check($request->otp, $user->password_otp) */
            ||
            now()->gt($user->password_otp_expires_at)
        ) {

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully'
        ], Response::HTTP_OK);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        $user->update([
            'password' => Hash::make($request->password),
            'password_otp' => null,
            'password_otp_expires_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successful'
        ], Response::HTTP_OK);
    }

    public function logout(Request $request)
    {
        $this->loginSessionService->recordLogout($request);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}
