<?php

namespace Database\Factories;

use App\Models\DocumentType;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentDocument>
 */
class StudentDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            's_id' => Student::query()->value('id'),
            'document_type_id' => DocumentType::factory(),
            'version' => 1,
            'disk' => 'local',
            'path' => 'students/test/document.pdf',
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'status' => 'submitted',
            'uploaded_by_role' => 'member',
            'uploaded_by_id' => 1,
            'uploaded_at' => now(),
        ];
    }
}
