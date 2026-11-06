<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveScientificSupervisorRequest;
use App\Models\Department;
use App\Models\ScientificSupervisor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScientificSupervisorController extends Controller
{
    public function index(): View
    {
        return view('admin.scientific-supervisors.index', [
            'supervisors' => ScientificSupervisor::query()
                ->with('department')
                ->withCount('students')
                ->orderBy('department_id')
                ->orderByDesc('active')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.scientific-supervisors.create', [
            'supervisor' => new ScientificSupervisor,
            'departments' => Department::query()->orderBy('tmhma')->get(['id', 'atlasId', 'tmhma']),
        ]);
    }

    public function store(SaveScientificSupervisorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            Department::query()->whereKey($data['department_id'])->lockForUpdate()->firstOrFail();
            ScientificSupervisor::query()
                ->where('department_id', $data['department_id'])
                ->update(['active' => false]);

            (new ScientificSupervisor)->forceFill($data + [
                'active' => true,
                'access_code' => $this->newAccessCode(),
            ])->save();
        });

        return redirect()->route('admin.scientific-supervisors.index')
            ->with('status', 'Ο νέος Επιστημονικός Υπεύθυνος δημιουργήθηκε και ενεργοποιήθηκε.');
    }

    public function edit(ScientificSupervisor $scientificSupervisor): View
    {
        return view('admin.scientific-supervisors.create', [
            'supervisor' => $scientificSupervisor->load('department'),
            'departments' => collect([$scientificSupervisor->department]),
        ]);
    }

    public function update(
        SaveScientificSupervisorRequest $request,
        ScientificSupervisor $scientificSupervisor,
    ): RedirectResponse {
        $data = $request->safe()->except('department_id');

        DB::transaction(function () use ($data, $scientificSupervisor): void {
            Department::query()->whereKey($scientificSupervisor->department_id)->lockForUpdate()->firstOrFail();

            if ((bool) ($data['active'] ?? false)) {
                ScientificSupervisor::query()
                    ->where('department_id', $scientificSupervisor->department_id)
                    ->where('id', '!=', $scientificSupervisor->id)
                    ->update(['active' => false]);
            }

            $scientificSupervisor->forceFill($data)->save();
        });

        return redirect()->route('admin.scientific-supervisors.index')
            ->with('status', 'Τα στοιχεία του Επιστημονικού Υπεύθυνου ενημερώθηκαν.');
    }

    public function activate(ScientificSupervisor $scientificSupervisor): RedirectResponse
    {
        DB::transaction(function () use ($scientificSupervisor): void {
            Department::query()->whereKey($scientificSupervisor->department_id)->lockForUpdate()->firstOrFail();
            ScientificSupervisor::query()
                ->where('department_id', $scientificSupervisor->department_id)
                ->update(['active' => false]);
            $scientificSupervisor->forceFill(['active' => true])->save();
        });

        return redirect()->route('admin.scientific-supervisors.index')
            ->with('status', 'Ο Επιστημονικός Υπεύθυνος ενεργοποιήθηκε για τις επόμενες εισαγωγές.');
    }

    private function newAccessCode(): string
    {
        do {
            $code = Str::random(16);
        } while (ScientificSupervisor::query()->where('access_code', $code)->exists());

        return $code;
    }
}
