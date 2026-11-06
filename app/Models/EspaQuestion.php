<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EspaQuestion extends Model
{
    protected $table = 'espaQuestions_new';

    protected $primaryKey = 'id_microdata';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'id_microdata' => 'integer',
            'id_patera' => 'integer',
            'epipedo' => 'integer',
        ];
    }
}
