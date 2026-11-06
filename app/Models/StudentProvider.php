<?php

namespace App\Models;

use App\Support\RescomReferenceData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProvider extends Model
{
    protected $table = 'providers';

    public $timestamps = false;

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 's_id');
    }

    public function legalFormLabel(): ?string
    {
        return config('praktiki_fields.select_options.f_nomikhMorfh_rescom.'.$this->f_nomikhMorfh_rescom);
    }

    public function organizationTypeLabel(): ?string
    {
        return config('praktiki_fields.select_options.dhm_idiwt_rescom.'.$this->dhm_idiwt_rescom);
    }

    public function taxOfficeLabel(): ?string
    {
        return app(RescomReferenceData::class)->label('doys', $this->f_doy_rescom);
    }

    public function headquartersAddress(): string
    {
        $city = app(RescomReferenceData::class)->label('cities', $this->f_polh_rescom) ?: $this->f_perioxh;
        $country = (int) $this->f_xwra_rescom === 300
            ? null
            : app(RescomReferenceData::class)->label('countries', $this->f_xwra_rescom);

        return collect([$city, $this->f_odos, $this->f_tk, $country])->filter()->implode(', ');
    }

    public function internshipAddress(): string
    {
        if (filled($this->f_ektPrakt_polh_rescom) && filled($this->f_ektPrakt_odos) && filled($this->f_ektPrakt_tk)) {
            return collect([
                app(RescomReferenceData::class)->label('cities', $this->f_ektPrakt_polh_rescom),
                $this->f_ektPrakt_odos,
                $this->f_ektPrakt_tk,
            ])->filter()->implode(', ');
        }

        return (string) $this->f_topos_ekt_prakt;
    }

    public function legalRepresentative(): string
    {
        $name = trim(($this->f_ekprFname ?? '').' '.($this->f_ekprLname ?? ''));

        return collect([$name, $this->f_ekprIdiothta])->filter()->implode(', ');
    }
}
