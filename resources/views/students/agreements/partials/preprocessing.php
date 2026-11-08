<?php   
    use App\Support\RescomReferenceData;


    /*
     * Κοινή προετοιμασία δεδομένων για όλα τα συμφωνητικά.
     */
    //headquartersAddress() εχει μεσα και ΤΚ 
    //internshipAddress() τοπο που θα γινει η πρακτικη με τκ
    //legalRepresentative() ονομα και ιδιοτητα νομικου εκπροσωπου φορεα
    //providerSupervisor() ονομα επιβλεποντα φορεα

    //foithths
    // dd($student);
    $fyloFoit_fromConfig=config('praktiki_fields.select_options.fylo_rescom.'.$student->fylo_rescom, '');
    $katEkp_fromConfig=config('praktiki_fields.select_options.katEkp_rescom.'.$student->katEkp_rescom, '');
    $wresPerWeek_fromConfig =config('praktiki_fields.select_options.wresPerWeek.'.$student->wresPerWeek, '');

    $xwraFoit_fromRescom=app(RescomReferenceData::class)->label('countries', $student->xwra_rescom);
    $doyFoit_fromRescom=app(RescomReferenceData::class)->label('doys', $student->doy_rescom);
    

    //foreas
    $formatDate = static function ($date) {
        return filled($date)
            ? \Illuminate\Support\Carbon::parse($date)->format('d/m/Y')
            : null;
    };

    $studentName = trim(
        ($student->fName ?? '') . ' ' . ($student->lName ?? '')
    );

    $supervisorName = trim(
        ($supervisor?->fName ?? '') . ' ' .
        ($supervisor?->lName ?? '')
    );

    // $providerRepresentative = trim(
    //     ($provider?->f_ekprFname ?? '') . ' ' .
    //     ($provider?->f_ekprLname ?? '')
    // );

    // $providerSupervisor = trim(
    //     ($provider?->f_epopt_foreaFname ?? '') . ' ' .
    //     ($provider?->f_epopt_foreaLname ?? '')
    // );

    $providerAfm = (string) ($provider?->f_afm ?? '');

    $startDate = $formatDate($student->startDate);
    $endDate = $formatDate($student->endDate);
    $startDateB = $formatDate($student->startDate_DKPT_B);
    $endDateA = $formatDate($student->endDate_DKPT_A);

    $dailyHours = $student->wresPerDays ?? null;
    $studentDoy = $student->doy_rescom ?? null;


    
    $amountWords = \App\Support\AgreementHelper::amountToGreekWords(
        $student->payTotal
    );
