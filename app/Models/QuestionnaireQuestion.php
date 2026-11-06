<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionnaireQuestion extends Model
{
    public $timestamps = false;

    protected $table = 'student_questionnaire_questions';

    protected function casts(): array
    {
        return ['options' => 'array'];
    }
}
