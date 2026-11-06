<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderQuestionnaireQuestion extends Model
{
    protected $table = 'provider_questionnaire_questions';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['options' => 'array'];
    }
}
