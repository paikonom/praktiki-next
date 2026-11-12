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
        \phpCAS::forceAuthentication();

        return response()->json([
            'user' => \phpCAS::getUser(),
            'attributes' => \phpCAS::getAttributes(),
        ], headers: ['Cache-Control' => 'no-store, private']);
    }
}
