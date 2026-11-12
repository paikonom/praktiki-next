<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CasInspectorController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless(config('services.cas.inspect_enabled'), 404);

        \phpCAS::client(
            SAML_VERSION_1_1,
            config('services.cas.host'),
            config('services.cas.port'),
            '',
            config('app.url'),
            false,
        );
        \phpCAS::setCasServerCACert(config('services.cas.ca_cert'));

        $laravelErrorHandler = null;
        $laravelErrorHandler = set_error_handler(
            function (int $severity, string $message, string $file, int $line) use (&$laravelErrorHandler): bool {
                if ($severity === E_WARNING
                    && str_contains($file, '/apereo/phpcas/')
                    && $message === 'Undefined array key 0') {
                    return true;
                }

                return is_callable($laravelErrorHandler)
                    ? (bool) $laravelErrorHandler($severity, $message, $file, $line)
                    : false;
            },
        );

        try {
            \phpCAS::forceAuthentication();

            return response()->json([
                'user' => \phpCAS::getUser(),
                'attributes' => \phpCAS::getAttributes(),
            ], headers: ['Cache-Control' => 'no-store, private']);
        } finally {
            restore_error_handler();
        }
    }
}
