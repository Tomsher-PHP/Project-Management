<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{

    public function showLogin()
    {
        // redirect to dashboard if already logged
        if (Auth::check()) {
            return redirect()->route('dashboard'); // or url('/dashboard')
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

        //status should be true to allow login
        $credentials['status'] = true;

        // Attempt login
        if (! Auth::attempt($credentials, $remember)) {
            return back()->with('error', 'Invalid credentials');
        }

        // Prevent session fixation
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Welcome back!');
    }

    // Send otp to forgot password mail
    public function sendOtp(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        $otp = random_int(10000, 99999);

        $user->update([
            'password_otp' => $otp, // for more secure | Hash::make($otp)
            'password_otp_expires_at' => now()->addMinutes(5),
        ]);

        // Send Mail
        Mail::raw("Your password reset OTP is: {$otp}", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Password Reset OTP');
        });

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'email' => $user->email
        ]);
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
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully'
        ]);
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
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}
