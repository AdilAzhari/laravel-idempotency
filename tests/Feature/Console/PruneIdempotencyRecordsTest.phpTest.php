<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Console\Commands\PruneIdempotencyRecords;

use AdilAzhari\LaravelIdempotency\Models\IdempotencyRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('prunes expired idempotency records', function (): void {

    IdempotencyRecord::query()->create([
        'key' => 'expired-key',
        'fingerprint' => 'fingerprint',
        'status' => 200,
        'headers' => [],
        'body' => '{}',
        'created_at' => now()->subDays(2),
        'expires_at' => now()->subDay(),
    ]);

    IdempotencyRecord::query()->create([
        'key' => 'active-key',
        'fingerprint' => 'fingerprint',
        'status' => 200,
        'headers' => [],
        'body' => '{}',
        'created_at' => now(),
        'expires_at' => now()->addDay(),
    ]);

    $this->artisan('idempotency:prune')
        ->expectsOutput(
            'Pruned 1 expired idempotency records.'
        )
        ->assertSuccessful();

    expect(IdempotencyRecord::query()->count())
        ->toBe(1);
});
