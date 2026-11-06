<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->string('funding_code', 30);
            $table->char('section', 1);
            $table->decimal('sort_order', 6, 2);
            $table->string('code', 100);
            $table->string('title');
            $table->string('responsible_role', 30);
            $table->string('available_after', 30)->default('always');
            $table->json('prerequisites');
            $table->string('source_type', 30);
            $table->string('source_key', 100);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['funding_code', 'code']);
            $table->index(['section', 'sort_order']);
        });

        Schema::create('workflow_section_rules', function (Blueprint $table) {
            $table->id();
            $table->string('funding_code', 30);
            $table->char('section', 1);
            $table->string('role', 30);
            $table->string('available_after', 30)->default('always');
            $table->json('completion_actions');
            $table->boolean('requires_previous_section')->default(true);
            $table->timestamps();
            $table->unique(['funding_code', 'section', 'role']);
        });

        $now = now();
        $actions = [
            ['all', 'A', 1, 'stoix_student', 'Εισαγωγή στοιχείων φοιτητή', 'student', 'always', [], 'action', 'stoix_student'],
            ['all', 'A', 2, 'stoix_forea', 'Εισαγωγή στοιχείων φορέα υποδοχής', 'student', 'always', [], 'action', 'stoix_forea'],
            ['all', 'A', 3, 'symf_initial', 'Δημιουργία αρχικού συμφωνητικού χωρίς υπογραφές', 'member', 'always', ['stoix_student', 'stoix_forea'], 'document', 'symf_initial'],
            ['all', 'A', 4, 'symf_sgn_foit_foreas', 'Ανέβασμα συμφωνητικού με υπογραφές φοιτητή και φορέα', 'student', 'always', ['symf_initial'], 'document', 'symf_SN_f_f'],
            ['Espa', 'A', 5, 'symf_sgn_foit_foreas_scientific_supervisor', 'Ανέβασμα συμφωνητικού με υπογραφές φοιτητή, φορέα και ΕΠ/ΥΠ', 'scientific_supervisor', 'always', ['symf_sgn_foit_foreas'], 'document', 'symf_SN_f_f_ey'],
            ['Espa', 'A', 6, 'symf_sgn_ready', 'Ανέβασμα τελικού συμφωνητικού με όλες τις υπογραφές', 'member', 'always', ['symf_sgn_foit_foreas_scientific_supervisor'], 'document', 'symf_SN_FULL'],
            ['Institution', 'A', 6, 'symf_sgn_ready', 'Ανέβασμα τελικού συμφωνητικού με όλες τις υπογραφές', 'member', 'always', ['symf_sgn_foit_foreas'], 'document', 'symf_SN_FULL'],
            ['InternshipProvider', 'A', 6, 'symf_sgn_ready', 'Ανέβασμα τελικού συμφωνητικού με όλες τις υπογραφές', 'member', 'always', ['symf_sgn_foit_foreas'], 'document', 'symf_SN_FULL'],
            ['Espa', 'A', 7, 'symf_ada', 'Ανέβασμα τελικού συμφωνητικού ΑΔΑ – Διαύγεια', 'member', 'always', ['symf_sgn_ready'], 'document', 'ADA_diaugeia'],
            ['Institution', 'A', 7, 'symf_ada', 'Ανέβασμα τελικού συμφωνητικού ΑΔΑ – Διαύγεια', 'member', 'always', ['symf_sgn_ready'], 'document', 'ADA_diaugeia'],
            ['all', 'A', 8, 'erganh_enarxh_upload', 'Ανέβασμα εντύπου έναρξης ΕΡΓΑΝΗ Ε3.5', 'provider', 'always', ['symf_sgn_ready'], 'document', 'erganh_enarxh'],
            ['Espa', 'B', 1, 'input_sumpl', 'Συμπλήρωση απογραφικού δελτίου εισόδου', 'student', 'internship_start', [], 'apografiko', 'input'],
            ['Espa', 'B', 2, 'input_upload', 'Ανέβασμα απογραφικού δελτίου εισόδου', 'student', 'internship_start', ['input_sumpl'], 'document', 'input'],
            ['Espa', 'G', 1, 'output_sumpl', 'Συμπλήρωση απογραφικού δελτίου εξόδου', 'student', 'internship_end', [], 'apografiko', 'output'],
            ['Espa', 'G', 2, 'output_upload', 'Ανέβασμα απογραφικού δελτίου εξόδου', 'student', 'internship_end', ['output_sumpl'], 'document', 'output'],
            ['all', 'G', 3, 'ekthesh_forea_sumpl', 'Συμπλήρωση έκθεσης επίδοσης από επόπτη φορέα', 'provider', 'internship_end', [], 'provider_questionnaire', 'provider'],
            ['all', 'G', 4, 'ekthesh_forea_upload', 'Ανέβασμα έκθεσης επίδοσης από επόπτη φορέα', 'provider', 'internship_end', ['ekthesh_forea_sumpl'], 'document', 'ekthesh_Forea'],
            ['all', 'G', 5, 'vev_olokl_forea_upload', 'Ανέβασμα βεβαίωσης ολοκλήρωσης φορέα', 'provider', 'internship_end', [], 'document', 'vevaiwsh_Forea'],
            ['all', 'G', 6, 'erganh_lhxh_upload', 'Ανέβασμα εντύπου λήξης ΕΡΓΑΝΗ Ε3.5', 'provider', 'internship_end', [], 'document', 'erganh_lhxh'],
            ['all', 'G', 7, 'ekthesh_foit_sumpl', 'Συμπλήρωση αναλυτικής έκθεσης πεπραγμένων φοιτητή', 'student', 'internship_end', [], 'student_questionnaire', 'student'],
            ['all', 'G', 8, 'ekthesh_foit_upload', 'Ανέβασμα αναλυτικής έκθεσης πεπραγμένων φοιτητή', 'student', 'internship_end', ['ekthesh_foit_sumpl'], 'document', 'ekthesh_Foit'],
            ['all', 'G', 9, 'hmer_prog_upload', 'Ανέβασμα ημερήσιου προγράμματος παρουσίας και δραστηριοτήτων', 'student', 'internship_end', [], 'document', 'hmer_programma'],
            ['all', 'G', 10, 'stoix_fys_pros_upload', 'Ανέβασμα δήλωσης στοιχείων φυσικού προσώπου', 'student', 'internship_end', [], 'document', 'dhl_fys_pros'],
            ['all', 'G', 11, 'trapeza_upload', 'Ανέβασμα στοιχείων τραπεζικού λογαριασμού', 'student', 'internship_end', [], 'document', 'bank_first'],
            ['all', 'G', 20, 'all_files_foit', 'Δήλωση ολοκλήρωσης εντύπων από φοιτητή', 'student', 'internship_end', [], 'action', 'all_files_foit'],
            ['all', 'G', 21, 'all_files_foreas', 'Δήλωση ολοκλήρωσης εντύπων από φορέα', 'provider', 'internship_end', [], 'action', 'all_files_foreas'],
            ['all', 'G', 22, 'all_files_scientific_supervisor', 'Δήλωση ολοκλήρωσης εντύπων από ΕΠ/ΥΠ', 'scientific_supervisor', 'internship_end', [], 'action', 'all_files_scientific_supervisor'],
            ['Espa', 'G', 23, 'atlas_file_upload', 'Ολοκλήρωση διαδικασίας ΑΤΛΑΣ', 'member', 'internship_end', [], 'document', 'f_atlas'],
            ['InternshipProvider', 'G', 24, 'enshma_foit_upload', 'Ανέβασμα βεβαίωσης ενσήμων e-ΕΦΚΑ', 'student', 'internship_end', [], 'document', 'vevaiwsh_enshmwn_foit'],
            ['InternshipProvider', 'G', 25, 'enshma_foreas_upload', 'Ανέβασμα βεβαίωσης ενσήμων φορέα', 'provider', 'internship_end', [], 'document', 'vevaiwsh_enshmwn_foreas'],
            ['InternshipProvider', 'G', 26, 'amoivh_trapeza_upload', 'Ανέβασμα αναλυτικής κατάστασης αμοιβής από την τράπεζα', 'student', 'internship_end', [], 'document', 'katastash_amoivhs'],
            ['Espa', 'A', 99, 'section_a_approval', 'Οριστική έγκριση Ενότητας Α', 'member', 'always', ['stoix_student', 'stoix_forea', 'symf_initial', 'symf_sgn_foit_foreas', 'symf_sgn_foit_foreas_scientific_supervisor', 'symf_sgn_ready', 'symf_ada', 'erganh_enarxh_upload'], 'action', 'section_a_approval'],
            ['Institution', 'A', 99, 'section_a_approval', 'Οριστική έγκριση Ενότητας Α', 'member', 'always', ['stoix_student', 'stoix_forea', 'symf_initial', 'symf_sgn_foit_foreas', 'symf_sgn_ready', 'symf_ada', 'erganh_enarxh_upload'], 'action', 'section_a_approval'],
            ['InternshipProvider', 'A', 99, 'section_a_approval', 'Οριστική έγκριση Ενότητας Α', 'member', 'always', ['stoix_student', 'stoix_forea', 'symf_initial', 'symf_sgn_foit_foreas', 'symf_sgn_ready', 'erganh_enarxh_upload'], 'action', 'section_a_approval'],
            ['Espa', 'B', 99, 'section_b_approval', 'Οριστική έγκριση Ενότητας Β', 'member', 'internship_start', ['section_a_approval', 'input_upload'], 'action', 'section_b_approval'],
            ['Espa', 'G', 99, 'section_g_approval', 'Οριστική έγκριση Ενότητας Γ', 'member', 'internship_end', ['section_b_approval', 'output_upload', 'ekthesh_forea_upload', 'vev_olokl_forea_upload', 'erganh_lhxh_upload', 'ekthesh_foit_upload', 'hmer_prog_upload', 'stoix_fys_pros_upload', 'trapeza_upload', 'all_files_foit', 'all_files_foreas', 'all_files_scientific_supervisor', 'atlas_file_upload'], 'action', 'section_g_approval'],
            ['Institution', 'G', 99, 'section_g_approval', 'Οριστική έγκριση Ενότητας Γ', 'member', 'internship_end', ['ekthesh_forea_upload', 'vev_olokl_forea_upload', 'erganh_lhxh_upload', 'ekthesh_foit_upload', 'hmer_prog_upload', 'stoix_fys_pros_upload', 'trapeza_upload', 'all_files_foit', 'all_files_foreas', 'all_files_scientific_supervisor'], 'action', 'section_g_approval'],
            ['InternshipProvider', 'G', 99, 'section_g_approval', 'Οριστική έγκριση Ενότητας Γ', 'member', 'internship_end', ['ekthesh_forea_upload', 'vev_olokl_forea_upload', 'erganh_lhxh_upload', 'ekthesh_foit_upload', 'hmer_prog_upload', 'stoix_fys_pros_upload', 'trapeza_upload', 'all_files_foit', 'all_files_foreas', 'all_files_scientific_supervisor', 'enshma_foit_upload', 'enshma_foreas_upload', 'amoivh_trapeza_upload'], 'action', 'section_g_approval'],
            ['all', 'D', 2, 'finish_pa', 'Επιτυχής ολοκλήρωση πρακτικής άσκησης', 'member', 'internship_end', ['section_g_approval'], 'action', 'finish_pa'],
            ['Espa', 'D', 1, 'aitima_plhrwmhs', 'Αποστολή αιτήματος πληρωμής φοιτητή', 'member', 'internship_end', ['section_g_approval'], 'action', 'aitima_plhrwmhs'],
            ['Espa', 'D', 2, 'finish_pa', 'Επιτυχής ολοκλήρωση πρακτικής άσκησης', 'member', 'internship_end', ['aitima_plhrwmhs'], 'action', 'finish_pa'],
        ];

        DB::table('workflow_actions')->insert(array_map(fn (array $action): array => [
            'funding_code' => $action[0], 'section' => $action[1], 'sort_order' => $action[2], 'code' => $action[3],
            'title' => $action[4], 'responsible_role' => $action[5], 'available_after' => $action[6],
            'prerequisites' => json_encode($action[7]), 'source_type' => $action[8], 'source_key' => $action[9],
            'active' => true, 'created_at' => $now, 'updated_at' => $now,
        ], $actions));

        $rules = [];
        foreach (['student', 'provider', 'scientific_supervisor', 'member'] as $role) {
            $rules[] = ['all', 'A', $role, 'always', 'section_a_approval', false];
            $rules[] = ['all', 'B', $role, 'internship_start', null, true];
            $rules[] = ['Espa', 'B', $role, 'internship_start', 'section_b_approval', true];
            $rules[] = ['all', 'G', $role, 'internship_end', 'section_g_approval', true];
            $rules[] = ['all', 'D', $role, 'internship_end', 'finish_pa', true];
        }

        DB::table('workflow_section_rules')->insert(array_map(fn (array $rule): array => [
            'funding_code' => $rule[0], 'section' => $rule[1], 'role' => $rule[2],
            'available_after' => $rule[3], 'completion_actions' => json_encode($rule[4] ? [$rule[4]] : []),
            'requires_previous_section' => $rule[5], 'created_at' => $now, 'updated_at' => $now,
        ], $rules));
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_section_rules');
        Schema::dropIfExists('workflow_actions');
    }
};
