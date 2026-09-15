<?php

namespace App\Console\Commands;

use App\Services\ApiClientService;
use Illuminate\Console\Command;

class RevokeApiClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-client:revoke {key_id : The public Key ID of the API client to revoke}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke an active server-to-server API client credential.';

    /**
     * Execute the console command.
     */
    public function handle(ApiClientService $clientService): int
    {
        $keyId = $this->argument('key_id');

        if (empty($keyId)) {
            $this->error('Key ID is required.');
            return self::FAILURE;
        }

        try {
            $client = $clientService->revokeClient((string) $keyId);

            $this->info('API client revoked successfully.');
            $this->newLine();
            $this->line("Name:   <comment>{$client->name}</comment>");
            $this->line("Key ID: <comment>{$client->key_id}</comment>");

            return self::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
