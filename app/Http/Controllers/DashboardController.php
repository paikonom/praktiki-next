<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $departments = DB::table('user_department_access')
            ->join('departments', 'departments.id', '=', 'user_department_access.department_id')
            ->where('user_department_access.user_id', $request->user()->id)
            ->select([
                'departments.atlasId',
                'departments.tmhma',
                'departments.sxolh',
                'departments.active',
                'user_department_access.can_manage_espa',
                'user_department_access.can_manage_institution',
                'user_department_access.can_manage_internship_provider',
            ])
            ->orderBy('departments.tmhma')
            ->get();

        $counts = [
            'access' => $departments->count(),
            'managed' => $departments->filter(fn (object $department): bool => $department->can_manage_espa
                || $department->can_manage_institution
                || $department->can_manage_internship_provider)->count(),
            'espa' => $departments->where('can_manage_espa', true)->count(),
            'institution' => $departments->where('can_manage_institution', true)->count(),
            'provider' => $departments->where('can_manage_internship_provider', true)->count(),
        ];

        return view('dashboard', compact('departments', 'counts'));
    }
}
