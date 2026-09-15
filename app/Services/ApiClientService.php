<?php

namespace App\Services;

use App\Models\ApiClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ApiClientService
{
    /**
     * Supported API scopes.
     */
    public const SUPPORTED_SCOPES = [
        ApiClient::SCOPE_MEETINGS_READ,
    ];

    /**
     * Create a new ApiClient record and return client instance with the raw API key.
     *
     * @return array{client: ApiClient, plain_text_key: string}
     *
     * @throws \InvalidArgumentException
     */
    public function createClient(string $name, array $scopes = [ApiClient::SCOPE_MEETINGS_READ], ?string $expiresAt = null): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Client name cannot be empty.');
        }

        $validatedScopes = $this->validateScopes($scopes);
        $parsedExpiresAt = $this->parseExpirationDate($expiresAt);

        $keyId = $this->generateUniqueKeyId();
        $secret = 'azo_sec_' . Str::random(40);
        $plainTextKey = $keyId . '.' . $secret;
        $secretHash = hash('sha256', $plainTextKey);

        $client = ApiClient::create([
            'name' => $name,
            'key_id' => $keyId,
            'secret_hash' => $secretHash,
            'scopes' => $validatedScopes,
            'is_active' => true,
            'expires_at' => $parsedExpiresAt,
        ]);

        return [
            'client' => $client,
            'plain_text_key' => $plainTextKey,
        ];
    }

    /**
     * Revoke an active ApiClient by setting is_active = false.
     *
     * @throws \InvalidArgumentException
     */
    public function revokeClient(string $keyId): ApiClient
    {
        $client = ApiClient::where('key_id', trim($keyId))->first();

        if (! $client) {
            throw new \InvalidArgumentException("API client with Key ID [{$keyId}] was not found.");
        }

        if (! $client->is_active) {
            throw new \InvalidArgumentException("API client [{$client->name}] (Key ID: {$keyId}) is already revoked.");
        }

        $client->update([
            'is_active' => false,
        ]);

        return $client;
    }

    /**
     * Rotate the API secret for an existing ApiClient.
     *
     * @return array{client: ApiClient, plain_text_key: string}
     *
     * @throws \InvalidArgumentException
     */
    public function rotateClient(string $keyId): array
    {
        $client = ApiClient::where('key_id', trim($keyId))->first();

        if (! $client) {
            throw new \InvalidArgumentException("API client with Key ID [{$keyId}] was not found.");
        }

        $newSecret = 'azo_sec_' . Str::random(40);
        $newPlainTextKey = $client->key_id . '.' . $newSecret;
        $newSecretHash = hash('sha256', $newPlainTextKey);

        $client->update([
            'secret_hash' => $newSecretHash,
        ]);

        return [
            'client' => $client,
            'plain_text_key' => $newPlainTextKey,
        ];
    }

    /**
     * Validate requested scopes against supported list.
     *
     * @throws \InvalidArgumentException
     */
    protected function validateScopes(array $scopes): array
    {
        if (empty($scopes)) {
            $scopes = [ApiClient::SCOPE_MEETINGS_READ];
        }

        foreach ($scopes as $scope) {
            if (! in_array($scope, self::SUPPORTED_SCOPES, true)) {
                throw new \InvalidArgumentException("Unsupported API scope [{$scope}]. Supported scopes: " . implode(', ', self::SUPPORTED_SCOPES));
            }
        }

        return array_values(array_unique($scopes));
    }

    /**
     * Parse and validate optional expiration date.
     *
     * @throws \InvalidArgumentException
     */
    protected function parseExpirationDate(?string $expiresAt): ?Carbon
    {
        if (empty($expiresAt) || strtolower(trim($expiresAt)) === 'never') {
            return null;
        }

        try {
            $date = Carbon::parse($expiresAt);
        } catch (\Throwable) {
            throw new \InvalidArgumentException("Invalid expiration date format [{$expiresAt}]. Provide a valid date (e.g. YYYY-MM-DD or YYYY-MM-DD HH:MM:SS).");
        }

        if ($date->isPast()) {
            throw new \InvalidArgumentException("Expiration date [{$expiresAt}] cannot be in the past.");
        }

        return $date;
    }

    /**
     * Generate a unique public Key ID.
     */
    protected function generateUniqueKeyId(): string
    {
        do {
            $keyId = 'azo_live_' . Str::lower(Str::random(10));
        } while (ApiClient::withTrashed()->where('key_id', $keyId)->exists());

        return $keyId;
    }
}
