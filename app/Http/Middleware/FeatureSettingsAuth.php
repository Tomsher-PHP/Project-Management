<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureSettingsAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->get('feature_settings_authenticated')) {
            return redirect()->route('feature-settings.login');
        }

        return $next($request);
    }
}
