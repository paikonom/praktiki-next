<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('title', 500);
            $table->text('instructions')->nullable();
            $table->unsignedInteger('max_size_mb')->default(15);
            $table->string('available_after', 30)->default('always');
            $table->boolean('active')->default(true);
            $table->decimal('sort_order', 6, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('document_type_fundings', function (Blueprint $table) {
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            $table->string('funding_code', 50);
            $table->primary(['document_type_id', 'funding_code']);
        });

        Schema::create('document_type_roles', function (Blueprint $table) {
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30);
            $table->boolean('can_view')->default(false);
            $table->boolean('can_upload')->default(false);
            $table->primary(['document_type_id', 'role']);
        });

        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('s_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('disk', 50)->nullable()->default('local');
            $table->string('path', 500)->nullable();
            $table->string('original_name', 500)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('status', 30)->default('submitted');
            $table->string('uploaded_by_role', 30)->nullable();
            $table->unsignedBigInteger('uploaded_by_id')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->unique(['s_id', 'document_type_id', 'version']);
            $table->index(['s_id', 'status']);
        });

        $types = [
            ['symf_SN_f_f', 'Ιδιωτικό συμφωνητικό (φοιτητής και φορέας)', 'always', 0, 'all', 'student,member'],
            ['symf_SN_f_f_ey', 'Ιδιωτικό συμφωνητικό με υπογραφή ΕΠ/ΥΠ', 'always', 1, 'Espa', 'scientific_supervisor,member'],
            ['symf_SN_FULL', 'Ιδιωτικό συμφωνητικό τελική μορφή', 'always', 2, 'all', 'member'],
            ['ADA_diaugeia', 'ΑΔΑ συμφωνητικού', 'always', 3, 'all', 'member'],
            ['vevaiwsh_EY', 'Βεβαίωση υπευθύνου Τμήματος', 'internship_end', 4, 'all', 'scientific_supervisor,member'],
            ['vevaiwsh_Forea', 'Βεβαίωση ολοκλήρωσης φορέα', 'internship_end', 5, 'all', 'provider,member'],
            ['ekthesh_Forea', 'Έκθεση επίδοσης από επόπτη φορέα', 'internship_end', 6, 'all', 'provider,member'],
            ['ekthesh_Foit', 'Αναλυτική έκθεση πεπραγμένων φοιτητή', 'internship_end', 7, 'all', 'student,member'],
            ['erganh_enarxh', 'Έντυπο έναρξης ΕΡΓΑΝΗ Ε3.5', 'always', 8, 'all', 'provider,member'],
            ['erganh_lhxh', 'Έντυπο λήξης ΕΡΓΑΝΗ Ε3.5', 'internship_end', 9, 'all', 'provider,member'],
            ['vevaiwsh_enshmwn_foit', 'Βεβαίωση ενσήμων e-ΕΦΚΑ από φοιτητή', 'internship_end', 10, 'InternshipProvider', 'student,member'],
            ['vevaiwsh_enshmwn_foreas', 'Βεβαίωση ενσήμων από φορέα', 'internship_end', 11, 'InternshipProvider', 'provider,member'],
            ['katastash_amoivhs', 'Αναλυτική κατάσταση αμοιβής από τράπεζα', 'internship_end', 12, 'InternshipProvider', 'student,member'],
            ['hmer_programma', 'Ημερήσιο πρόγραμμα παρουσίας και δραστηριοτήτων', 'internship_end', 13, 'all', 'student,member'],
            ['dhl_fys_pros', 'Δήλωση στοιχείων φυσικού προσώπου', 'internship_end', 14, 'Espa,Institution', 'student,member'],
            ['bank_first', 'Στοιχεία τραπεζικού λογαριασμού', 'internship_end', 15, 'all', 'student,member'],
            ['input', 'Απογραφικό δελτίο εισόδου', 'always', 16, 'Espa', 'student,member'],
            ['output', 'Απογραφικό δελτίο εξόδου', 'internship_end', 17, 'Espa', 'student,member'],
            ['f_atlas', 'Ολοκλήρωση ΑΤΛΑΣ', 'internship_end', 18, 'all', 'member'],
        ];

        foreach ($types as [$code, $title, $availableAfter, $sortOrder, $fundings, $uploaders]) {
            $typeId = DB::table('document_types')->insertGetId([
                'code' => $code, 'title' => $title, 'available_after' => $availableAfter,
                'sort_order' => $sortOrder, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $fundingCodes = $fundings === 'all' ? ['Espa', 'Institution', 'InternshipProvider'] : explode(',', $fundings);
            foreach ($fundingCodes as $fundingCode) {
                DB::table('document_type_fundings')->insert(['document_type_id' => $typeId, 'funding_code' => $fundingCode]);
            }
            foreach (['student', 'provider', 'scientific_supervisor', 'member'] as $role) {
                DB::table('document_type_roles')->insert([
                    'document_type_id' => $typeId, 'role' => $role, 'can_view' => true,
                    'can_upload' => in_array($role, explode(',', $uploaders), true),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
        Schema::dropIfExists('document_type_roles');
        Schema::dropIfExists('document_type_fundings');
        Schema::dropIfExists('document_types');
    }
};
