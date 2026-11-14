<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentAccessController extends Controller
{
    public function __invoke(): View
    {
        $departments = DB::table('departments')
            ->leftJoin('user_department_access', 'departments.id', '=', 'user_department_access.department_id')
            ->leftJoin('users', 'users.id', '=', 'user_department_access.user_id')
            ->select([
                'departments.id as department_id',
                'departments.atlasId',
                'departments.polh',
                'departments.tmhma',
                'departments.active',
                'users.id as user_id',
                'users.username',
                'users.name as user_name',
                'users.role',
                'users.view_only',
                'user_department_access.can_manage_espa',
                'user_department_access.can_manage_institution',
                'user_department_access.can_manage_internship_provider',
            ])
            ->orderBy('departments.tmhma')
            ->orderBy('users.name')
            ->get()
            ->groupBy('department_id');

        return view('admin.department-access', compact('departments'));
    }

    public function edit(int $department): View
    {
        $departmentRecord = DB::table('departments')->find($department);

        abort_if($departmentRecord === null, 404);

        $users = DB::table('users')->orderBy('name')->get();
        $accessByUserId = DB::table('user_department_access')
            ->where('department_id', $department)
            ->get()
            ->keyBy('user_id');

        return view('admin.department-access-edit', compact('departmentRecord', 'users', 'accessByUserId'));
    }

    public function update(Request $request, int $department): RedirectResponse
    {
        abort_unless(DB::table('departments')->where('id', $department)->exists(), 404);

        $validated = $request->validate([
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'manage_espa' => ['sometimes', 'array'],
            'manage_espa.*' => ['integer', 'distinct', 'exists:users,id'],
            'manage_institution' => ['sometimes', 'array'],
            'manage_institution.*' => ['integer', 'distinct', 'exists:users,id'],
            'manage_internship_provider' => ['sometimes', 'array'],
            'manage_internship_provider.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);
        $userIds = array_map('intval', $validated['user_ids'] ?? []);
        $espaUserIds = array_map('intval', $validated['manage_espa'] ?? []);
        $institutionUserIds = array_map('intval', $validated['manage_institution'] ?? []);
        $providerUserIds = array_map('intval', $validated['manage_internship_provider'] ?? []);
        $allUserIds = array_values(array_unique(array_merge($userIds, $espaUserIds, $institutionUserIds, $providerUserIds)));
        $now = now();

        DB::transaction(function () use ($department, $allUserIds, $espaUserIds, $institutionUserIds, $providerUserIds, $now): void {
            DB::table('user_department_access')->where('department_id', $department)->delete();

            if ($allUserIds !== []) {
                DB::table('user_department_access')->insert(array_map(fn (int $userId): array => [
                    'user_id' => $userId,
                    'department_id' => $department,
                    'can_manage_espa' => in_array($userId, $espaUserIds, true),
                    'can_manage_institution' => in_array($userId, $institutionUserIds, true),
                    'can_manage_internship_provider' => in_array($userId, $providerUserIds, true),
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $allUserIds));
            }
        });

        return redirect()
            ->route('admin.department-access')
            ->with('status', 'Η πρόσβαση του τμήματος ενημερώθηκε.');
    }
}
