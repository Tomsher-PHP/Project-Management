<?php

namespace App\Console\Commands;

use App\Services\ApiClientService;
use Illuminate\Console\Command;

class RotateApiClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-client:rotate {key_id : The public Key ID of the API client to rotate secret for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate the API secret for an existing API client.';

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
            $result = $clientService->rotateClient((string) $keyId);
            $client = $result['client'];
            $newPlainTextKey = $result['plain_text_key'];

            $this->info('API client key rotated successfully.');
            $this->newLine();
            $this->line("Name:        <comment>{$client->name}</comment>");
            $this->line("Key ID:      <comment>{$client->key_id}</comment>");
            $this->line("New API Key: <info>{$newPlainTextKey}</info>");
            $this->newLine();
            $this->warn('STORE THIS NEW API KEY SECURELY. IT WILL NOT BE SHOWN AGAIN. THE OLD KEY IS NOW INVALID.');

            return self::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
