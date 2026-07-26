<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Models\IdempotencyRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\PendingCommand;

pest()->use(RefreshDatabase::class);
it('removes expired idempotency records', function (): void {

    IdempotencyRecord::query()->create([
        'key' => 'expired-key',
        'fingerprint' => 'fingerprint',
        'status' => 200,
        'headers' => [],
        'body' => '{}',
        'expires_at' => now()->subDay(),
    ]);

    IdempotencyRecord::query()->create([
        'key' => 'active-key',
        'fingerprint' => 'fingerprint',
        'status' => 200,
        'headers' => [],
        'body' => '{}',
        'expires_at' => now()->addDay(),
    ]);

    /** @var PendingCommand $command */
    $command = $this->artisan('idempotency:prune');

    $command->expectsOutput('Pruned 1 expired idempotency records.')
        ->assertSuccessful()
        ->run();

    expect(IdempotencyRecord::query()
        ->where('key', 'expired-key')
        ->exists()
    )->toBeFalse()
        ->and(IdempotencyRecord::query()
            ->where('key', 'active-key')
            ->exists()
        )->toBeTrue();

});
