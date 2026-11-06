<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $visibility = [
            'symf_SN_f_f' => ['student', 'scientific_supervisor', 'member'],
            'symf_SN_f_f_ey' => ['scientific_supervisor', 'member'],
            'symf_SN_FULL' => ['student', 'provider', 'scientific_supervisor', 'member'],
            'ADA_diaugeia' => ['student', 'provider', 'member'],
            'vevaiwsh_EY' => ['scientific_supervisor', 'member'],
            'vevaiwsh_Forea' => ['student', 'provider', 'member'],
            'ekthesh_Forea' => ['provider', 'member'],
            'ekthesh_Foit' => ['student', 'member'],
            'erganh_enarxh' => ['provider', 'member'],
            'erganh_lhxh' => ['provider', 'member'],
            'vevaiwsh_enshmwn_foit' => ['student', 'member'],
            'vevaiwsh_enshmwn_foreas' => ['provider', 'member'],
            'katastash_amoivhs' => ['student', 'member'],
            'hmer_programma' => ['student', 'member'],
            'dhl_fys_pros' => ['student', 'member'],
            'bank_first' => ['student', 'member'],
            'input' => ['student', 'member'],
            'output' => ['student', 'member'],
            'f_atlas' => ['member'],
        ];

        foreach ($visibility as $code => $roles) {
            $typeId = DB::table('document_types')->where('code', $code)->value('id');
            DB::table('document_type_roles')->where('document_type_id', $typeId)->update(['can_view' => false]);
            DB::table('document_type_roles')->where('document_type_id', $typeId)->whereIn('role', $roles)->update(['can_view' => true]);
        }
    }

    public function down(): void
    {
        DB::table('document_type_roles')->update(['can_view' => true]);
    }
};
