<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePraktikiRequest;
use App\Models\Funding;
use App\Models\Praktiki;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PraktikesController extends Controller
{
    public function index(Request $request): View
    {
        $years = Praktiki::query()->distinct()->orderByDesc('praxh_year')->pluck('praxh_year');
        $fundings = Funding::query()->where('active', true)->orderBy('sort_order')->get();
        $year = $request->string('year')->toString();
        $year = $years->contains($year) ? $year : ($years->first() ?? '');
        $funding = $request->string('funding', 'Espa')->toString();
        $funding = $fundings->pluck('code')->contains($funding) ? $funding : ($fundings->first()?->code ?? '');

        $praktiki = Praktiki::query()
            ->with('funding')
            ->where('praxh_year', $year)
            ->whereHas('funding', fn ($query) => $query->where('code', $funding))
            ->first();

        return view('praktikes.index', compact('praktiki', 'years', 'fundings', 'year', 'funding'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Praktiki::class);

        $fundings = Funding::query()->where('active', true)->orderBy('sort_order')->get();
        $funding = $request->string('funding', 'Espa')->toString();
        $funding = $fundings->pluck('code')->contains($funding) ? $funding : ($fundings->first()?->code ?? '');

        return view('praktikes.form', compact('fundings', 'funding') + ['praktiki' => null]);
    }

    public function store(SavePraktikiRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $funding = Funding::query()->where('code', $request->fundingCode())->firstOrFail();

        $praktiki = new Praktiki;
        $praktiki->forceFill([
            'funding_id' => $funding->id,
            'praxh_year' => $validated['praxh_year'],
            'details' => $request->detailValues() ?: null,
            'notes' => $validated['notes'] ?? null,
            'active' => $validated['active'],
        ])->save();

        return redirect()->route('praktikes.index', ['year' => $validated['praxh_year'], 'funding' => $funding->code])
            ->with('success', 'Η πρακτική δημιουργήθηκε.');
    }

    public function edit(Praktiki $praktiki): View
    {
        Gate::authorize('update', $praktiki);
        $praktiki->load('funding');

        return view('praktikes.form', [
            'praktiki' => $praktiki,
            'fundings' => collect(),
            'funding' => $praktiki->funding_code,
        ]);
    }

    public function update(SavePraktikiRequest $request, Praktiki $praktiki): RedirectResponse
    {
        $validated = $request->validated();
        $praktiki->load('funding');

        $praktiki->forceFill([
            'praxh_year' => $validated['praxh_year'],
            'details' => $request->detailValues() ?: null,
            'notes' => $validated['notes'] ?? null,
            'active' => $validated['active'],
        ])->save();

        return redirect()->route('praktikes.index', [
            'year' => $validated['praxh_year'],
            'funding' => $praktiki->funding_code,
        ])->with('success', 'Η πρακτική ενημερώθηκε.');
    }
}
