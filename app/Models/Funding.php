<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Funding extends Model
{
    protected $table = 'fundings';

    public function praktikes(): HasMany
    {
        return $this->hasMany(Praktiki::class, 'funding_id');
    }
}
