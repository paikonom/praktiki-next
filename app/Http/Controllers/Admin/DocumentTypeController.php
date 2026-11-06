<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveDocumentTypeRequest;
use App\Models\DocumentType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DocumentTypeController extends Controller
{
    public function index(): View
    {
        return view('admin.document-types.index', [
            'types' => DocumentType::with(['fundings', 'roles'])->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.document-types.form', ['documentType' => new DocumentType]);
    }

    public function store(SaveDocumentTypeRequest $request): RedirectResponse
    {
        $documentType = new DocumentType;
        $this->save($documentType, $request->validated());

        return redirect()->route('admin.document-types.index')->with('status', 'Ο τύπος εγγράφου δημιουργήθηκε.');
    }

    public function edit(DocumentType $documentType): View
    {
        return view('admin.document-types.form', ['documentType' => $documentType->load(['fundings', 'roles'])]);
    }

    public function update(SaveDocumentTypeRequest $request, DocumentType $documentType): RedirectResponse
    {
        $this->save($documentType, $request->validated());

        return redirect()->route('admin.document-types.index')->with('status', 'Ο τύπος εγγράφου ενημερώθηκε.');
    }

    /** @param array<string, mixed> $data */
    private function save(DocumentType $documentType, array $data): void
    {
        DB::transaction(function () use ($documentType, $data): void {
            $documentType->fill(Arr::except($data, ['fundings', 'view_roles', 'upload_roles']));
            $documentType->active = (bool) ($data['active'] ?? false);
            $documentType->save();
            $documentType->fundings()->delete();
            $documentType->roles()->delete();
            foreach ($data['fundings'] as $funding) {
                $documentType->fundings()->create(['funding_code' => $funding]);
            }
            foreach ($data['view_roles'] as $role) {
                $documentType->roles()->create([
                    'role' => $role,
                    'can_view' => true,
                    'can_upload' => in_array($role, $data['upload_roles'] ?? [], true),
                ]);
            }
        });
    }
}
