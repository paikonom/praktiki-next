<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Praktiki extends Model
{
    protected $table = 'praktiki_cycles';

    public function funding(): BelongsTo
    {
        return $this->belongsTo(Funding::class);
    }

    public function phases(): HasMany
    {
        return $this->hasMany(Fasi::class, 'praktiki_cycle_id');
    }

    protected function fundingCode(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->funding?->code);
    }

    protected function fundingName(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->funding?->name);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'details' => 'array',
        ];
    }
}
