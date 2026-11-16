<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class FeatureSettingsAuthController extends Controller
{
    public function showLogin()
    {
        return view('feature-updates.settings-login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $passwordHash = config('feature_updates.settings_password');

        if (
            empty($passwordHash) ||
            !Hash::check($request->password, $passwordHash)
        ) {
            throw ValidationException::withMessages([
                'password' => 'Invalid management password.',
            ]);
        }

        // Do not regenerate the existing PMS session.
        // Only mark Feature Settings as authenticated.
        $request->session()->put(
            'feature_settings_authenticated',
            true
        );

        return redirect()->route('feature-settings.index');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('feature_settings_authenticated');

        $request->session()->regenerateToken();

        return redirect()->route('feature-settings.login');
    }
}
