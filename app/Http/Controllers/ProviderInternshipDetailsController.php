<?php

namespace App\Http\Controllers;

use App\Support\RescomReferenceData;
use App\Support\VisibleInternshipDetails;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProviderInternshipDetailsController extends Controller
{
    public function __invoke(
        Request $request,
        VisibleInternshipDetails $visibleInternshipDetails,
        RescomReferenceData $rescomReferenceData,
    ): View {
        $student = $request->user('provider');
        $section = $request->route('section', 'internship');
        $sectionDetails = [
            'internship' => [
                'keys' => ['internship', 'phase', 'department'],
                'activePage' => 'details',
                'title' => 'Στοιχεία πρακτικής',
            ],
            'student' => [
                'keys' => ['student'],
                'activePage' => 'student-details',
                'title' => 'Στοιχεία φοιτητή',
            ],
            'provider' => [
                'keys' => ['provider'],
                'activePage' => 'provider-details',
                'title' => 'Στοιχεία φορέα',
            ],
        ][$section];

        $internshipDetailGroups = collect($visibleInternshipDetails->forRole(
            $student,
            $rescomReferenceData,
            'provider',
        ))->whereIn('key', $sectionDetails['keys'])->values();

        return view('shared.internship-details-page', [
            'student' => $student,
            'portalRole' => 'provider',
            'activePortalPage' => $sectionDetails['activePage'],
            'pageTitle' => $sectionDetails['title'],
            'internshipDetailGroups' => $internshipDetailGroups,
            'flat' => $section !== 'internship',
        ]);
    }
}
