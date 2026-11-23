<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\RescomContract;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentRescomController extends Controller
{
    public function preview(Student $student, RescomContract $contract): View|RedirectResponse
    {
        Gate::authorize('submitToRescom', $student);

        try {
            $data = $contract->data($student);
        } catch (ValidationException $exception) {
            return $this->failure($student, implode(' ', array_merge(...array_values($exception->errors()))));
        }

        return view('students.rescom-preview', [
            'student' => $student,
            'data' => $data,
            'signature' => $this->signature($student, $data),
        ]);
    }

    public function store(Request $request, Student $student, RescomContract $contract): RedirectResponse
    {
        Gate::authorize('submitToRescom', $student);
        $url = (string) config('services.rescom.url');
        $token = (string) config('services.rescom.token');
        if (blank($token) || ! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return $this->failure($student, 'Δεν έχει ρυθμιστεί έγκυρη σύνδεση HTTPS με το RESCOM.');
        }

        try {
            $data = $contract->data($student);
        } catch (ValidationException $exception) {
            return $this->failure($student, implode(' ', array_merge(...array_values($exception->errors()))));
        }

        if (! hash_equals($this->signature($student, $data), $request->string('signature')->toString())) {
            return $this->failure($student, 'Τα στοιχεία άλλαξαν. Ανοίξτε ξανά την προεπισκόπηση.');
        }

        // Claim this student before sending, including while another request is in flight.
        $submission = [
            'status' => 'pending',
            'sent_by_user_id' => $request->user()->id,
            'sent_at' => now()->toIso8601String(),
        ];
        $claimed = Student::query()->whereKey($student->id)
            ->where(fn ($query) => $query->whereNull('rescom_resp')->orWhere('rescom_resp->_submission->status', 'rejected'))
            ->update(['rescom_resp' => json_encode(['_submission' => $submission], JSON_THROW_ON_ERROR)]);
        if (! $claimed) {
            return $this->failure($student, 'Υπάρχει ήδη απάντηση ή εκκρεμής αποστολή RESCOM για αυτή την πρακτική.');
        }

        $uncertain = 'Η αποστολή δεν επιβεβαιώθηκε. Ελέγξτε στο RESCOM αν δημιουργήθηκε σύμβαση πριν από νέα προσπάθεια.';
        try {
            $response = Http::acceptJson()->withoutRedirecting()->timeout(60)
                ->post($url, ['Authentication' => $token, 'Data' => $data]);
        } catch (\Illuminate\Http\Client\ConnectionException) {
            return $this->failure($student, $uncertain);
        }

        $result = $response->json();
        $success = is_array($result) ? ($result['WasSuccessful'] ?? null) : null;
        if ($response->successful() && in_array($success, [true, 1, '1'], true)) {
            unset($result['Authentication']);
            $result['_submission'] = array_replace($submission, ['status' => 'accepted']);
            $student->forceFill(['rescom_resp' => $result])->save();

            return redirect()->route('students.show', $student)->with('status', 'Η αίτηση στάλθηκε επιτυχώς στο RESCOM.');
        }

        if (($response->successful() || $response->clientError()) && in_array($success, [false, 0, '0'], true)) {
            $result = $this->redactResponse($result, $token);
            $message = collect($result)->only(['ErrorMessage', 'Message', 'Errors', 'ErrorMessages', 'ValidationErrors', 'errors', 'message'])
                ->flatten()->filter(fn ($value) => is_string($value) && filled($value))->unique()->implode(' · ');
            $message = Str::limit(trim(strip_tags($message)), 500);
            $student->forceFill(['rescom_resp' => [
                '_submission' => array_replace($submission, ['status' => 'rejected']),
                'response' => $result,
            ]])->save();

            return $this->failure($student, 'Το RESCOM απέρριψε την αίτηση. '.($message ?: 'Δείτε την πλήρη απάντηση στο «Δες ResCom».'));
        }

        return $this->failure($student, $uncertain.' (HTTP '.$response->status().')');
    }

    private function redactResponse(array $response, string $token): array
    {
        $safe = [];
        foreach ($response as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), ['authentication', 'authorization', 'token', 'access_token', 'password'], true)) {
                continue;
            }

            $key = is_string($key) ? str_replace($token, '[REDACTED]', $key) : $key;
            $safe[$key] = match (true) {
                is_array($value) => $this->redactResponse($value, $token),
                is_string($value) => str_replace($token, '[REDACTED]', $value),
                default => $value,
            };
        }

        return $safe;
    }

    private function signature(Student $student, array $data): string
    {
        return hash_hmac('sha256', json_encode([$student->id, $data], JSON_THROW_ON_ERROR), config('app.key'));
    }

    private function failure(Student $student, string $message): RedirectResponse
    {
        return redirect()->route('students.show', $student)
            ->with('status_type', 'danger')
            ->with('status', $message);
    }
}
