<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Json;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('student_apografika')
            ->select(['id', 'answers', 'request_payload', 'response_payload'])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $values = [];

                    foreach (['answers', 'request_payload', 'response_payload'] as $column) {
                        if ($row->{$column} === null) {
                            continue;
                        }

                        try {
                            $values[$column] = Crypt::decryptString($row->{$column});
                        } catch (Throwable) {
                            // Already stored as plain text.
                        }
                    }

                    if (isset($values['answers'])) {
                        $values['answers'] = Json::encode(Json::decode($values['answers'], true));
                    }

                    if ($values !== []) {
                        DB::table('student_apografika')->where('id', $row->id)->update($values);
                    }
                }
            });
    }

    public function down(): void
    {
        // Plain payloads intentionally remain readable.
    }
};
