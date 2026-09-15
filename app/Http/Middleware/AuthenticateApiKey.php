<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized();
        }

        $token = trim(substr($header, 7));

        if ($token === '') {
            return $this->unauthorized();
        }

        $secretHash = hash('sha256', $token);

        $client = ApiClient::query()
            ->where('secret_hash', $secretHash)
            ->where('is_active', true)
            ->first();

        if (! $client) {
            return $this->unauthorized();
        }

        if ($client->expires_at !== null && $client->expires_at->isPast()) {
            return $this->unauthorized();
        }

        ApiClient::where('id', $client->id)->update([
            'last_used_at' => now(),
        ]);

        $request->attributes->set('api_client', $client);

        return $next($request);
    }

    /**
     * Return a standardized 401 Unauthorized JSON response.
     */
    protected function unauthorized(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
        ], 401);
    }
}
