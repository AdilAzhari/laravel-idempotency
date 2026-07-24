<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property string $fingerprint
 * @property int $status
 * @property array<string, list<string|null>> $headers
 * @property string $body
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $expires_at
 */
final class IdempotencyRecord extends Model
{
    /**
     * @var array<int, string>
     */
    #[\Override]
    protected $fillable = [
        'key',
        'fingerprint',
        'status',
        'headers',
        'body',
        'expires_at',
    ];

    /**
     * @var array<string, 'array'|'immutable_datetime'>
     */
    #[\Override]
    protected $casts = [
        'headers' => 'array',
        'created_at' => 'immutable_datetime',
        'expires_at' => 'immutable_datetime',
    ];
}
