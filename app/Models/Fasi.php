<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fasi extends Model
{
    protected $table = 'praktiki_phases';

    public function praktiki(): BelongsTo
    {
        return $this->belongsTo(Praktiki::class, 'praktiki_cycle_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'fash_id');
    }

    protected function fundingCode(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->praktiki?->funding_code);
    }

    protected function fundingName(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->praktiki?->funding_name);
    }

    protected function praxhYear(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->praktiki?->praxh_year);
    }

    protected function tmhma(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->department?->tmhma);
    }

    protected function atlasId(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->department?->atlasId);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['details' => 'array'];
    }
}
