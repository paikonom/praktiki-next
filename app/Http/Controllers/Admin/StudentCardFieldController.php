<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveStudentCardFieldRequest;
use App\Models\StudentCardField;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class StudentCardFieldController extends Controller
{
    public function index(): View
    {
        return view('admin.student-card-fields.index', [
            'fieldGroups' => StudentCardField::query()
                ->orderBy('card_section')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->groupBy('card_section'),
        ]);
    }

    public function edit(StudentCardField $studentCardField): View
    {
        return view('admin.student-card-fields.edit', compact('studentCardField'));
    }

    public function update(SaveStudentCardFieldRequest $request, StudentCardField $studentCardField): RedirectResponse
    {
        $studentCardField->update($request->validated());

        return redirect()
            ->route('admin.student-card-fields.index')
            ->with('status', 'Το πεδίο ενημερώθηκε.');
    }
}
