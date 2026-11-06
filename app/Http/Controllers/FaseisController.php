<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveFasiRequest;
use App\Models\Department;
use App\Models\Fasi;
use App\Models\Funding;
use App\Models\Praktiki;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FaseisController extends Controller
{
    public function index(Request $request): View
    {
        $fundings = Funding::query()->where('active', true)->orderBy('sort_order')->get();
        $yearsQuery = DB::table('praktiki_phases')
            ->join('praktiki_cycles', 'praktiki_cycles.id', '=', 'praktiki_phases.praktiki_cycle_id');

        if ($request->user()->role !== 'admin') {
            $yearsQuery
                ->join('user_department_access', 'user_department_access.department_id', '=', 'praktiki_phases.department_id')
                ->where('user_department_access.user_id', $request->user()->id);
        }

        $years = $yearsQuery->distinct()->orderByDesc('praktiki_cycles.praxh_year')->pluck('praktiki_cycles.praxh_year');
        $year = $request->string('year')->toString();
        $year = $years->contains($year) ? $year : ($years->first() ?? '');
        $funding = $request->string('funding', 'Espa')->toString();
        $funding = $fundings->pluck('code')->contains($funding) ? $funding : ($fundings->first()?->code ?? '');

        $departments = $this->departmentsForIndex($request);
        $phases = Fasi::query()
            ->with('praktiki.funding')
            ->whereIn('department_id', $departments->pluck('id'))
            ->whereHas('praktiki', fn ($query) => $query->where('praxh_year', $year)
                ->whereHas('funding', fn ($query) => $query->where('code', $funding)))
            ->orderBy('name')
            ->get()
            ->groupBy('department_id');

        return view('faseis.index', compact('departments', 'phases', 'years', 'fundings', 'year', 'funding'));
    }

    public function create(Request $request): View
    {
        $praktikes = Praktiki::query()
            ->with('funding')
            ->where('active', true)
            ->orderByDesc('praxh_year')
            ->get()
            ->sortBy('funding.sort_order');
        $praktiki = $praktikes->firstWhere('id', $request->integer('praktiki_cycle_id'));

        if (! $praktiki && $request->filled(['year', 'funding'])) {
            $praktiki = $praktikes->first(fn (Praktiki $item): bool => $item->praxh_year === $request->string('year')->toString()
                && $item->funding_code === $request->string('funding')->toString());
        }

        $praktiki ??= $praktikes->first();
        $departments = $praktiki ? $this->manageableDepartments($request, $praktiki->funding_code) : collect();

        return view('faseis.form', [
            'phase' => null,
            'praktiki' => $praktiki,
            'praktikes' => $praktikes,
            'departments' => $departments,
            'phaseNames' => SaveFasiRequest::PHASE_NAMES,
        ]);
    }

    public function store(SaveFasiRequest $request): RedirectResponse
    {
        $praktiki = $request->praktiki();
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $praktiki): void {
            // shortcut: legacy_fid remains required until the legacy identifier is removed from the schema.
            $legacyFid = ((int) Fasi::query()->lockForUpdate()->max('legacy_fid')) + 1;
            $fasi = new Fasi;
            $fasi->forceFill([
                'legacy_fid' => $legacyFid,
                'praktiki_cycle_id' => $praktiki->id,
                'department_id' => $validated['department_id'],
                ...$request->commonValues(),
                'details' => $request->detailValues(),
            ])->save();
        });

        return redirect()->route('faseis.index', [
            'year' => $praktiki->praxh_year,
            'funding' => $praktiki->funding_code,
        ])->with('success', 'Η φάση δημιουργήθηκε.');
    }

    public function edit(Fasi $fasi): View
    {
        Gate::authorize('update', $fasi);
        $fasi->load(['praktiki.funding', 'department']);

        return view('faseis.form', [
            'phase' => $fasi,
            'praktiki' => $fasi,
            'praktikes' => collect(),
            'departments' => collect(),
            'phaseNames' => SaveFasiRequest::PHASE_NAMES,
        ]);
    }

    public function update(SaveFasiRequest $request, Fasi $fasi): RedirectResponse
    {
        $fasi->load('praktiki.funding');

        DB::transaction(function () use ($request, $fasi): void {
            $fasi->forceFill([
                ...$request->commonValues(),
                'details' => $request->detailValues(),
            ])->save();
        });

        return redirect()->route('faseis.index', [
            'year' => $fasi->praxh_year,
            'funding' => $fasi->funding_code,
        ])->with('success', 'Η φάση ενημερώθηκε.');
    }

    private function manageableDepartments(Request $request, string $funding): Collection
    {
        if ($request->user()->view_only) {
            return collect();
        }

        $query = $request->user()->role === 'admin'
            ? Department::query()
            : $request->user()->manageableDepartments($funding);

        return $query
            ->select('departments.id', 'departments.atlasId', 'departments.tmhma')
            ->orderBy('departments.tmhma')
            ->get();
    }

    private function departmentsForIndex(Request $request): Collection
    {
        $query = DB::table('departments');

        if ($request->user()->role === 'admin') {
            $query->select([
                'departments.id', 'departments.atlasId', 'departments.tmhma', 'departments.sxolh',
                DB::raw('1 as can_manage_espa'),
                DB::raw('1 as can_manage_institution'),
                DB::raw('1 as can_manage_internship_provider'),
            ]);
        } else {
            $query
                ->join('user_department_access', 'user_department_access.department_id', '=', 'departments.id')
                ->where('user_department_access.user_id', $request->user()->id)
                ->select([
                    'departments.id', 'departments.atlasId', 'departments.tmhma', 'departments.sxolh',
                    'user_department_access.can_manage_espa',
                    'user_department_access.can_manage_institution',
                    'user_department_access.can_manage_internship_provider',
                ]);
        }

        return $query->orderBy('departments.tmhma')->get();
    }
}
