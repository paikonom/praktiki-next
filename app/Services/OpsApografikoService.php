<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentApografiko;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OpsApografikoService
{
    private const SOAP_NAMESPACE = 'http://schemas.xmlsoap.org/soap/envelope/';

    private const PARTICIPANTS_NAMESPACE = 'urn:espa:v6:services:participants';

    private const WSSE_NAMESPACE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

    private const WSU_NAMESPACE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';

    public function buildSubmissionRequest(Student $student, StudentApografiko $apografiko): string
    {
        $student->loadMissing('phase.praktiki');
        $this->assertConfigured();

        $afm = trim((string) $student->afm);
        $amf = trim((string) $student->AMF);
        $gender = match ((int) $student->fylo_rescom) {
            1 => 5301,
            2 => 5302,
            default => throw new RuntimeException('Δεν έχει οριστεί έγκυρο φύλο για την αποστολή στο ΟΠΣ.'),
        };

        if (! preg_match('/^[0-9]{9}$/', $afm)) {
            throw new RuntimeException('Το ΟΠΣ απαιτεί ΑΦΜ ακριβώς 9 ψηφίων.');
        }

        if ($amf === '') {
            throw new RuntimeException('Δεν έχει οριστεί Αριθμός Μητρώου Φοιτητή (ΑΜΦ).');
        }

        $date = $apografiko->type === 'input' ? $student->startDate : $student->endDate;
        if (! $date) {
            throw new RuntimeException('Δεν έχει οριστεί η απαιτούμενη ημερομηνία έναρξης/λήξης.');
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $envelope = $document->createElementNS(self::SOAP_NAMESPACE, 'soapenv:Envelope');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:urn', self::PARTICIPANTS_NAMESPACE);
        $document->appendChild($envelope);

        $header = $this->append($document, $envelope, self::SOAP_NAMESPACE, 'soapenv:Header');
        $security = $this->append($document, $header, self::WSSE_NAMESPACE, 'wsse:Security');
        $security->setAttributeNS(self::SOAP_NAMESPACE, 'soapenv:mustUnderstand', '1');
        $security->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:wsu', self::WSU_NAMESPACE);
        $token = $this->append($document, $security, self::WSSE_NAMESPACE, 'wsse:UsernameToken');
        $token->setAttributeNS(self::WSU_NAMESPACE, 'wsu:Id', 'UsernameToken-'.Str::upper(Str::random(24)));
        $this->appendText($document, $token, self::WSSE_NAMESPACE, 'wsse:Username', config('praktiki_apografika.username'));
        $password = $this->appendText($document, $token, self::WSSE_NAMESPACE, 'wsse:Password', config('praktiki_apografika.password'));
        $password->setAttribute('Type', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText');
        $nonce = $this->appendText($document, $token, self::WSSE_NAMESPACE, 'wsse:Nonce', base64_encode(random_bytes(16)));
        $nonce->setAttribute('EncodingType', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary');
        $this->appendText($document, $token, self::WSU_NAMESPACE, 'wsu:Created', now('UTC')->format('Y-m-d\TH:i:s.v\Z'));

        $body = $this->append($document, $envelope, self::SOAP_NAMESPACE, 'soapenv:Body');
        $request = $this->append($document, $body, self::PARTICIPANTS_NAMESPACE, 'urn:SymmetexontesRequest');
        $participant = $this->append($document, $request, self::PARTICIPANTS_NAMESPACE, 'urn:OfeloumenosInput');
        $this->appendText($document, $participant, self::PARTICIPANTS_NAMESPACE, 'urn:KatigTaytopoihsis', '1');
        $this->appendText($document, $participant, self::PARTICIPANTS_NAMESPACE, 'urn:IDTaytopoihsis', $afm);
        $this->appendText($document, $participant, self::PARTICIPANTS_NAMESPACE, 'urn:Gender', (string) $gender);

        $form = $this->append($document, $participant, self::PARTICIPANTS_NAMESPACE, 'urn:DeltioOfeloumenou');
        $this->appendText($document, $form, self::PARTICIPANTS_NAMESPACE, 'urn:EidosDeltiou', $apografiko->type === 'input' ? '1' : '0');
        $this->appendText($document, $form, self::PARTICIPANTS_NAMESPACE, 'urn:HmniaDeltiou', $date->format('Y-m-d'));
        $this->appendText($document, $form, self::PARTICIPANTS_NAMESPACE, 'urn:KodikosMis', $this->mis($student));
        $this->appendText($document, $form, self::PARTICIPANTS_NAMESPACE, 'urn:IDOfelDikaiouxou', $amf);
        $this->appendText($document, $form, self::PARTICIPANTS_NAMESPACE, 'urn:IDPeriferias', (string) config('praktiki_apografika.region_id'));
        $this->appendText($document, $form, self::PARTICIPANTS_NAMESPACE, 'urn:PliresFlag', '1');
        $this->appendText($document, $form, self::PARTICIPANTS_NAMESPACE, 'urn:DioikitikesPigesFlag', '1');
        $this->appendText($document, $form, self::PARTICIPANTS_NAMESPACE, 'urn:FlagOloklirosis', $apografiko->type === 'input' ? '0' : '1');

        foreach ($apografiko->answers ?? [] as $id => $answer) {
            if ($answer === null) {
                continue;
            }

            $microdata = $this->append($document, $form, self::PARTICIPANTS_NAMESPACE, 'urn:DeltioMicrodata');
            $this->appendText($document, $microdata, self::PARTICIPANTS_NAMESPACE, 'urn:IDMicrodata', (string) $id);
            $this->appendText($document, $microdata, self::PARTICIPANTS_NAMESPACE, 'urn:Apantisi', (string) $answer);
        }

        return $document->saveXML() ?: throw new RuntimeException('Δεν δημιουργήθηκε το XML του απογραφικού δελτίου.');
    }

    public function buildProgressRequest(string $requestProgressId): string
    {
        $this->assertConfigured();
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $envelope = $document->createElementNS(self::SOAP_NAMESPACE, 'soapenv:Envelope');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:urn', self::PARTICIPANTS_NAMESPACE);
        $document->appendChild($envelope);
        $header = $this->append($document, $envelope, self::SOAP_NAMESPACE, 'soapenv:Header');
        $security = $this->append($document, $header, self::WSSE_NAMESPACE, 'wsse:Security');
        $security->setAttributeNS(self::SOAP_NAMESPACE, 'soapenv:mustUnderstand', '1');
        $security->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:wsu', self::WSU_NAMESPACE);
        $token = $this->append($document, $security, self::WSSE_NAMESPACE, 'wsse:UsernameToken');
        $token->setAttributeNS(self::WSU_NAMESPACE, 'wsu:Id', 'UsernameToken-'.Str::upper(Str::random(24)));
        $this->appendText($document, $token, self::WSSE_NAMESPACE, 'wsse:Username', config('praktiki_apografika.username'));
        $password = $this->appendText($document, $token, self::WSSE_NAMESPACE, 'wsse:Password', config('praktiki_apografika.password'));
        $password->setAttribute('Type', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText');
        $nonce = $this->appendText($document, $token, self::WSSE_NAMESPACE, 'wsse:Nonce', base64_encode(random_bytes(16)));
        $nonce->setAttribute('EncodingType', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary');
        $this->appendText($document, $token, self::WSU_NAMESPACE, 'wsu:Created', now('UTC')->format('Y-m-d\TH:i:s.v\Z'));
        $body = $this->append($document, $envelope, self::SOAP_NAMESPACE, 'soapenv:Body');
        $request = $this->append($document, $body, self::PARTICIPANTS_NAMESPACE, 'urn:SymmetexontesRequest');
        $this->appendText($document, $request, self::PARTICIPANTS_NAMESPACE, 'urn:RequestProgressID', $requestProgressId);

        return $document->saveXML() ?: throw new RuntimeException('Δεν δημιουργήθηκε το XML ελέγχου του απογραφικού δελτίου.');
    }

    /** @return array{status: string, response: string, request_progress_id?: string, deltio_id?: int, error_code?: string, error_message?: string} */
    public function send(string $payload, string $operation): array
    {
        $response = Http::timeout(config('praktiki_apografika.timeout'))
            ->withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => $operation,
            ])
            ->withBody($payload, 'text/xml')
            ->post(config('praktiki_apografika.endpoint'))
            ->throw();

        return $this->parseResponse($response->body());
    }

    public function sanitizedRequest(string $payload): string
    {
        return preg_replace(
            '/(<wsse:Password\b[^>]*>).*?(<\/wsse:Password>)/s',
            '$1[REDACTED]$2',
            $payload,
        ) ?? $payload;
    }

    /** @return array{status: string, response: string, request_progress_id?: string, deltio_id?: int, error_code?: string, error_message?: string} */
    private function parseResponse(string $xml): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return ['status' => StudentApografiko::STATUS_FAILED, 'response' => $xml, 'error_code' => 'invalid_xml', 'error_message' => 'Το ΟΠΣ επέστρεψε μη έγκυρο XML.'];
        }

        $xpath = new DOMXPath($document);
        $fault = $this->firstText($xpath, '//*[local-name()="Fault"]/*[local-name()="faultstring"]');
        if ($fault !== null) {
            return ['status' => StudentApografiko::STATUS_FAILED, 'response' => $xml, 'error_code' => 'soap_fault', 'error_message' => $fault];
        }

        $progressMessage = $this->firstText($xpath, '//*[local-name()="RequestProgressMessage"]');
        if ($progressMessage !== null) {
            $progressId = $this->progressId($progressMessage);

            return $progressId === null
                ? ['status' => StudentApografiko::STATUS_FAILED, 'response' => $xml, 'error_code' => 'missing_progress_id', 'error_message' => $progressMessage]
                : ['status' => StudentApografiko::STATUS_PROCESSING, 'response' => $xml, 'request_progress_id' => $progressId];
        }

        $generalError = $this->firstText($xpath, '//*[local-name()="GeneralErrorMessage"]');
        $errorCode = $this->firstText($xpath, '//*[local-name()="DeltioOfeloumenou"]/*[local-name()="ErrorCode"]');
        $errorMessage = $this->firstText($xpath, '//*[local-name()="DeltioOfeloumenou"]/*[local-name()="ErrorMessage"]') ?? $generalError;
        $deltioId = $this->completedDeltioId(
            $this->firstText($xpath, '//*[local-name()="DeltioOfeloumenou"]/*[local-name()="IDDeltiou"]'),
            $errorMessage,
        );

        if ($errorCode === '0' && $deltioId !== null) {
            return [
                'status' => StudentApografiko::STATUS_COMPLETED,
                'response' => $xml,
                'deltio_id' => $deltioId,
                'error_code' => '0',
                'error_message' => $errorMessage ?? 'Το απογραφικό δελτίο ολοκληρώθηκε.',
            ];
        }

        return [
            'status' => StudentApografiko::STATUS_FAILED,
            'response' => $xml,
            'error_code' => $errorCode ?? 'unknown_response',
            'error_message' => $errorMessage ?? 'Το ΟΠΣ επέστρεψε μη αναγνωρίσιμη απάντηση.',
        ];
    }

    private function completedDeltioId(?string $deltioId, ?string $message): ?int
    {
        if ($deltioId !== null && ctype_digit($deltioId)) {
            return (int) $deltioId;
        }

        return preg_match('/[ΑA]\\/[ΑA]:\\s*(\\d+)/u', $message ?? '', $matches) === 1
            ? (int) $matches[1]
            : null;
    }

    private function progressId(string $message): ?string
    {
        if (preg_match('/χρησιμοποι[^\s]*\s+το\s+([^\s.]+)\s*\.?$/ui', $message, $matches) === 1) {
            return trim($matches[1]);
        }

        return preg_match('/^\s*([A-Za-z0-9._-]{6,})\s*$/', $message, $matches) === 1
            ? $matches[1]
            : null;
    }

    private function firstText(DOMXPath $xpath, string $expression): ?string
    {
        $node = $xpath->query($expression)?->item(0);
        $value = trim($node?->textContent ?? '');

        return $value === '' ? null : $value;
    }

    private function mis(Student $student): string
    {
        $mis = trim((string) (config('praktiki_apografika.mis_override') ?: data_get($student->phase?->praktiki?->details, 'kwdikos_OPS')));

        if ($mis === '' || ! ctype_digit($mis)) {
            throw new RuntimeException('Δεν έχει οριστεί έγκυρος κωδικός ΟΠΣ/MIS για την πρακτική.');
        }

        return $mis;
    }

    private function assertConfigured(): void
    {
        if (! config('praktiki_apografika.enabled')) {
            throw new RuntimeException('Η σύνδεση απογραφικών με το ΟΠΣ δεν είναι ενεργοποιημένη.');
        }

        foreach (['endpoint', 'username', 'password'] as $key) {
            if (blank(config("praktiki_apografika.{$key}"))) {
                throw new RuntimeException('Λείπει η ρύθμιση OPS_APOGRAFIKA_'.Str::upper($key).'.');
            }
        }
    }

    private function append(DOMDocument $document, DOMElement $parent, string $namespace, string $name): DOMElement
    {
        $element = $document->createElementNS($namespace, $name);
        $parent->appendChild($element);

        return $element;
    }

    private function appendText(DOMDocument $document, DOMElement $parent, string $namespace, string $name, string $value): DOMElement
    {
        $element = $this->append($document, $parent, $namespace, $name);
        $element->appendChild($document->createTextNode($value));

        return $element;
    }
}
