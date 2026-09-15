<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use App\Services\ApiClientService;
use Illuminate\Console\Command;

class CreateApiClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-client:create 
                            {--name= : Human-readable name of the external application}
                            {--scope=* : Allowed API scope(s) (e.g. meetings.read)}
                            {--expires-at= : Optional expiration date (e.g. 2027-12-31 or "never")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new server-to-server API client credential.';

    /**
     * Execute the console command.
     */
    public function handle(ApiClientService $clientService): int
    {
        $name = $this->option('name');

        if (empty($name)) {
            $name = $this->ask('Enter the application/client name (e.g. External Calendar App)');
        }

        if (empty(trim((string) $name))) {
            $this->error('Client name is required.');
            return self::FAILURE;
        }

        $scopes = (array) $this->option('scope');
        if (empty($scopes)) {
            $scopes = [ApiClient::SCOPE_MEETINGS_READ];
        }

        $expiresAt = $this->option('expires-at');

        try {
            $result = $clientService->createClient($name, $scopes, $expiresAt);
            $client = $result['client'];
            $plainTextKey = $result['plain_text_key'];

            $this->info('API client created successfully.');
            $this->newLine();
            $this->line("Name:      <comment>{$client->name}</comment>");
            $this->line("Key ID:    <comment>{$client->key_id}</comment>");
            $this->line("API Key:   <info>{$plainTextKey}</info>");
            $this->line("Scopes:    <comment>" . implode(', ', $client->scopes ?? []) . '</comment>');
            $this->line('Expires:   <comment>' . ($client->expires_at ? $client->expires_at->toDateTimeString() : 'Never') . '</comment>');
            $this->newLine();
            $this->warn('STORE THIS API KEY SECURELY. IT WILL NOT BE SHOWN AGAIN.');

            return self::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
