<?php

namespace Spatie\LaravelPasskeys\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 *
 * @property \Illuminate\Support\Collection<\Spatie\LaravelPasskeys\Models\Passkey> $passkeys
 */
interface HasPasskeys
{
    public function passkeys(): HasMany;

    public function getPassKeyName(): string;

    public function getPassKeyId(): string;

    public function getPassKeyDisplayName(): string;
}
