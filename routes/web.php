<?php

use App\Http\Controllers\Admin\DepartmentAccessController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\ScientificSupervisorController;
use App\Http\Controllers\Admin\StudentCardFieldController;
use App\Http\Controllers\Admin\WorkflowActionController;
use App\Http\Controllers\Admin\WorkflowSectionRuleController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ProviderAuthenticatedSessionController;
use App\Http\Controllers\Auth\StudentAuthenticatedSessionController;
use App\Http\Controllers\Auth\SupervisorLoginController;
use App\Http\Controllers\CompletionCertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FaseisController;
use App\Http\Controllers\PortalDashboardController;
use App\Http\Controllers\PraktikesController;
use App\Http\Controllers\ProviderInternshipDetailsController;
use App\Http\Controllers\ProviderQuestionnaireController;
use App\Http\Controllers\RescomCitiesController;
use App\Http\Controllers\StudentAgreementController;
use App\Http\Controllers\StudentApografikoController;
use App\Http\Controllers\StudentApografikoReviewController;
use App\Http\Controllers\StudentEmailController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\StudentQuestionnaireController;
use App\Http\Controllers\StudentQuestionnaireReviewController;
use App\Http\Controllers\StudentsController;
use App\Http\Controllers\StudentWorkflowActionController;
use App\Http\Controllers\SupervisorPortalController;
use App\Http\Middleware\EnsureSelectedInternshipIsActive;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:view-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/department-access', DepartmentAccessController::class)
        ->name('department-access');
    Route::get('/departments/{department}/access', [DepartmentAccessController::class, 'edit'])
        ->name('department-access.edit');
    Route::put('/departments/{department}/access', [DepartmentAccessController::class, 'update'])
        ->name('department-access.update');
    Route::resource('document-types', DocumentTypeController::class)
        ->parameters(['document-types' => 'documentType'])
        ->except(['show', 'destroy']);
    Route::resource('student-card-fields', StudentCardFieldController::class)
        ->parameters(['student-card-fields' => 'studentCardField'])
        ->only(['index', 'edit', 'update']);
    Route::resource('scientific-supervisors', ScientificSupervisorController::class)
        ->parameters(['scientific-supervisors' => 'scientificSupervisor'])
        ->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('/scientific-supervisors/{scientificSupervisor}/activate', [ScientificSupervisorController::class, 'activate'])
        ->name('scientific-supervisors.activate');
    Route::resource('workflow-actions', WorkflowActionController::class)->except('show');
    Route::resource('workflow-section-rules', WorkflowSectionRuleController::class)->except('show');
});

Route::get('/', function () {
    return view('home');
})
    // an kapoios einai logged in ekteleitai to redirect pou orizoume sto middleware tou app.php
    // dld to redirectUsersTo() kai den emfanizetai to home page
    ->middleware('guest:web,student,provider,scientific_supervisor')
    ->name('home');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest:web,student,provider,scientific_supervisor')
    ->name('login');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest:web,student,provider,scientific_supervisor', 'throttle:login']);

Route::prefix('student')->name('student.')->group(function () {
    Route::get('/login', [StudentAuthenticatedSessionController::class, 'create'])
        ->middleware('guest:web,student,provider,scientific_supervisor')
        ->name('login');
    Route::post('/login', [StudentAuthenticatedSessionController::class, 'store'])
        ->middleware(['guest:web,student,provider,scientific_supervisor', 'throttle:5,1']);
    Route::get('/internships', [StudentAuthenticatedSessionController::class, 'internships'])
        ->middleware('guest:web,student,provider,scientific_supervisor')
        ->name('internships');
    Route::post('/internships', [StudentAuthenticatedSessionController::class, 'selectInternship'])
        ->middleware('guest:web,student,provider,scientific_supervisor')
        ->name('internships.select');

    Route::middleware(['auth:student', EnsureSelectedInternshipIsActive::class])->group(function () {
        Route::get('/dashboard', PortalDashboardController::class)->name('dashboard');
        Route::get('/details', [StudentPortalController::class, 'details'])->name('details');
        Route::get('/details/student', [StudentPortalController::class, 'editStudentDetails'])->name('details.student.edit');
        Route::patch('/details/student', [StudentPortalController::class, 'updateStudentDetails'])->name('details.student.update');
        Route::get('/details/provider', [StudentPortalController::class, 'editProviderDetails'])->name('details.provider.edit');
        Route::patch('/details/provider', [StudentPortalController::class, 'updateProviderDetails'])->name('details.provider.update');
        Route::get('/records', [StudentPortalController::class, 'records'])->name('records');
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
        Route::post('/documents/{documentType}', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/file/{studentDocument}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
        Route::get('/documents/file/{studentDocument}', [DocumentController::class, 'download'])->name('documents.download');
        Route::delete('/documents/file/{studentDocument}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::get('/questionnaire', [StudentQuestionnaireController::class, 'show'])->name('questionnaire');
        Route::get('/questionnaire/pdf', [StudentQuestionnaireController::class, 'pdf'])->name('questionnaire.pdf');
        Route::post('/questionnaire', [StudentQuestionnaireController::class, 'store'])->name('questionnaire.store');
        Route::get('/apografika/{type}', [StudentApografikoController::class, 'show'])
            ->whereIn('type', ['input', 'output'])
            ->name('apografika.show');
        Route::post('/apografika/{type}', [StudentApografikoController::class, 'store'])
            ->whereIn('type', ['input', 'output'])
            ->middleware('throttle:5,1')
            ->name('apografika.store');
        Route::post('/apografika/{type}/check', [StudentApografikoController::class, 'check'])
            ->whereIn('type', ['input', 'output'])
            ->middleware('throttle:10,1')
            ->name('apografika.check');
        Route::get('/apografika/{type}/pdf', [StudentApografikoController::class, 'pdf'])
            ->whereIn('type', ['input', 'output'])
            ->name('apografika.pdf');
        Route::get('/contact', [StudentPortalController::class, 'contact'])->name('contact');
        Route::get('/rescom/cities', RescomCitiesController::class)
            ->middleware('throttle:60,1')
            ->name('rescom.cities');
        Route::post('/logout', [StudentAuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});

Route::prefix('provider')->name('provider.')->group(function () {
    Route::get('/login', [ProviderAuthenticatedSessionController::class, 'create'])
        ->middleware('guest:web,student,provider,scientific_supervisor')
        ->name('login');
    Route::post('/login', [ProviderAuthenticatedSessionController::class, 'store'])
        ->middleware(['guest:web,student,provider,scientific_supervisor', 'throttle:5,1']);

    Route::middleware('auth:provider')->group(function () {
        Route::get('/dashboard', PortalDashboardController::class)->name('dashboard');
        Route::get('/details', ProviderInternshipDetailsController::class)->name('details');
        Route::get('/details/{section}', ProviderInternshipDetailsController::class)
            ->whereIn('section', ['student', 'provider'])
            ->name('details.section');
        Route::get('/questionnaire', [ProviderQuestionnaireController::class, 'show'])->name('questionnaire');
        Route::post('/questionnaire', [ProviderQuestionnaireController::class, 'store'])->name('questionnaire.store');
        Route::get('/questionnaire/pdf', [ProviderQuestionnaireController::class, 'pdf'])->name('questionnaire.pdf');
        Route::get('/completion-certificate', [CompletionCertificateController::class, 'providerPortal'])
            ->name('completion-certificate.preview');
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
        Route::post('/documents/{documentType}', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/file/{studentDocument}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
        Route::get('/documents/file/{studentDocument}', [DocumentController::class, 'download'])->name('documents.download');
        Route::delete('/documents/file/{studentDocument}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('/logout', [ProviderAuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});

Route::prefix('scientific-supervisor')->name('scientific_supervisor.')->group(function () {
    Route::get('/login', [SupervisorLoginController::class, 'create'])
        ->middleware('guest:web,student,provider,scientific_supervisor')
        ->name('login');
    Route::post('/login', [SupervisorLoginController::class, 'store'])
        ->middleware(['guest:web,student,provider,scientific_supervisor', 'throttle:5,1']);

    Route::middleware('auth:scientific_supervisor')->group(function () {
        Route::get('/dashboard', [SupervisorPortalController::class, 'dashboard'])
            ->name('dashboard');
        Route::get('/students/{student}', [SupervisorPortalController::class, 'showStudent'])
            ->name('students.show');
        Route::get('/students/{student}/completion-certificate', [CompletionCertificateController::class, 'supervisor'])
            ->name('completion-certificate.preview');
        Route::post('/students/{student}/documents/{documentType}', [DocumentController::class, 'store']
        )->name('documents.store');
        Route::get('/documents/{studentDocument}/preview', [DocumentController::class, 'preview'])
            ->name('documents.preview');
        Route::get('/documents/{studentDocument}', [DocumentController::class, 'download'])
            ->name('documents.download');
        Route::delete('/documents/{studentDocument}', [DocumentController::class, 'destroy'])
            ->name('documents.destroy');
        Route::post('/logout', [SupervisorLoginController::class, 'destroy'])
            ->name('logout');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/students', [StudentsController::class, 'index'])->name('students.index');
    Route::get('/students/import', [StudentImportController::class, 'create'])->name('students.import.create');
    Route::post('/students/import', [StudentImportController::class, 'store'])->name('students.import.store');
    Route::post('/students/import/check', [StudentImportController::class, 'check'])->name('students.import.check');
    Route::get('/rescom/cities', RescomCitiesController::class)
        ->middleware('throttle:60,1')
        ->name('rescom.cities');
    Route::get('/students/{student}', [StudentsController::class, 'show'])->name('students.show');
    Route::get('/students/{student}/emails/{type}/preview', [StudentEmailController::class, 'preview'])->name('students.emails.preview');
    Route::post('/students/{student}/emails/{type}', [StudentEmailController::class, 'store'])->middleware('throttle:10,1')->name('students.emails.store');
    Route::get('/students/{student}/agreement/preview', [StudentAgreementController::class, 'preview'])->name('students.agreement.preview');
    Route::get('/students/{student}/completion-certificates/provider', [CompletionCertificateController::class, 'provider'])
        ->name('students.completion-certificates.provider');
    Route::get('/students/{student}/completion-certificates/supervisor', [CompletionCertificateController::class, 'supervisor'])
        ->name('students.completion-certificates.supervisor');
    Route::get('/students/{student}/documents', [DocumentController::class, 'index'])->name('students.documents.index');
    Route::post('/students/{student}/documents/{documentType}', [DocumentController::class, 'store'])->name('students.documents.store');
    Route::post('/students/{student}/documents/{documentType}/not-required', [DocumentController::class, 'markNotRequired'])->name('students.documents.not-required');
    Route::get('/student-documents/{studentDocument}/preview', [DocumentController::class, 'preview'])->name('students.documents.preview');
    Route::get('/student-documents/{studentDocument}', [DocumentController::class, 'download'])->name('students.documents.download');
    Route::patch('/student-documents/{studentDocument}', [DocumentController::class, 'update'])->name('students.documents.update');
    Route::delete('/student-documents/{studentDocument}', [DocumentController::class, 'destroy'])->name('students.documents.destroy');
    Route::get('/students/{student}/questionnaires/{type}', [StudentQuestionnaireReviewController::class, 'show'])
        ->whereIn('type', ['student', 'provider'])
        ->name('students.questionnaires.show');
    Route::get('/students/{student}/questionnaires/{type}/pdf', [StudentQuestionnaireReviewController::class, 'pdf'])
        ->whereIn('type', ['student', 'provider'])
        ->name('students.questionnaires.pdf');
    Route::patch('/students/{student}/questionnaires/{type}', [StudentQuestionnaireReviewController::class, 'update'])
        ->whereIn('type', ['student', 'provider'])
        ->name('students.questionnaires.update');
    Route::get('/students/{student}/apografika/{type}/pdf', [StudentApografikoReviewController::class, 'pdf'])
        ->whereIn('type', ['input', 'output'])
        ->name('students.apografika.pdf');
    Route::patch('/students/{student}/apografika/{type}', [StudentApografikoReviewController::class, 'update'])
        ->whereIn('type', ['input', 'output'])
        ->name('students.apografika.update');
    Route::patch('/students/{student}/{section}', [StudentsController::class, 'updateSection'])
        ->whereIn('section', ['student', 'provider', 'office'])
        ->name('students.section.update');
    Route::post('/students/{student}/workflow-actions/{action}', [StudentWorkflowActionController::class, 'store'])
        ->name('students.workflow-actions.store');
    Route::resource('faseis', FaseisController::class)
        ->parameters(['faseis' => 'fasi'])
        ->only(['index', 'create', 'store', 'edit', 'update']);
    Route::get('/praktikes', [PraktikesController::class, 'index'])->name('praktikes.index');
    Route::middleware('can:view-admin')->group(function () {
        Route::resource('praktikes', PraktikesController::class)
            ->parameters(['praktikes' => 'praktiki'])
            ->only(['create', 'store', 'edit', 'update']);
    });
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
