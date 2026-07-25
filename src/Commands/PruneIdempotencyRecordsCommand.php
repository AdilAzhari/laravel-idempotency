<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Commands;

use AdilAzhari\LaravelIdempotency\Models\IdempotencyRecord;
use Illuminate\Console\Command;

final class PruneIdempotencyRecordsCommand extends Command
{
    #[\Override]
    protected $signature = 'idempotency:prune';

    #[\Override]
    protected $description = 'Remove expired idempotency records';

    public function handle(): int
    {
        $deleted = IdempotencyRecord::query()
            ->where('expires_at', '<=', now())
            ->delete();

        $this->info(
            sprintf(
                'Pruned %d expired idempotency records.',
                $deleted,
            )
        );

        return self::SUCCESS;
    }
}
