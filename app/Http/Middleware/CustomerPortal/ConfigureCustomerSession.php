<?php

namespace App\Http\Middleware\CustomerPortal;

use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Symfony\Component\HttpFoundation\Response;

class ConfigureCustomerSession
{
    public function __construct(
        private readonly Container $app,
        private readonly ConfigRepository $config,
        private readonly SessionManager $sessionManager,
        private readonly AuthManager $authManager,
    ) {}

    /**
     * Apply isolated Customer Portal session settings for the duration of the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $originalSessionConfig = [
            'session.cookie' => $this->config->get('session.cookie'),
            'session.path' => $this->config->get('session.path'),
            'session.lifetime' => $this->config->get('session.lifetime'),
        ];

        $this->config->set([
            'session.cookie' => $this->config->get('customer_portal.session.cookie', 'customer_portal_session'),
            'session.path' => $this->config->get('customer_portal.session.path', '/c-portal'),
            'session.lifetime' => (int) $this->config->get('customer_portal.session.lifetime', 120),
        ]);

        // Clear cached session drivers, singleton session.store, redirector, and guards so StartSession and Auth resolve the customer store.
        $this->sessionManager->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $this->app->forgetInstance('redirect');
        $this->authManager->forgetGuards();

        try {
            return $next($request);
        } finally {
            $this->config->set($originalSessionConfig);
            $this->sessionManager->forgetDrivers();
            $this->app->forgetInstance('session.store');
            $this->app->forgetInstance('redirect');
            $this->authManager->forgetGuards();
        }
    }
}
