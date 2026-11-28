<?php

return [

    'session' => [
        'cookie' => 'customer_portal_session',
        'path' => '/c-portal',
        'lifetime' => 120,
    ],

    'authentication' => [
        'guard' => 'customer',
        'max_login_attempts' => 5,
        'login_decay_seconds' => 60,
    ],

    'password_reset' => [
        'otp_length' => 6,
        'otp_expiry_minutes' => 5,
        'max_verification_attempts' => 5,
        'resend_interval_seconds' => 60,
        'max_requests_per_hour' => 5,
        'reset_token_expiry_minutes' => 10,
    ],

];
