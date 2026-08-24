<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property string $fingerprint
 * @property string $job_class
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $expires_at
 */
#[Fillable('key', 'fingerprint', 'job_class', 'expires_at')]
final class JobIdempotencyRecord extends Model
{
    /**
     * @var array<string, 'immutable_datetime'>
     */
    #[\Override]
    protected $casts = [
        'created_at' => 'immutable_datetime',
        'expires_at' => 'immutable_datetime',
    ];
}
