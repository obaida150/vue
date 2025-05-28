<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Füge hier die API-Routen hinzu, die Probleme mit CSRF haben
        'api/events',
        'api/events/*',
        'api/events/*/delete',
        'api/events/week-plan',
        'api/events/*/approve',
        'api/*',
        'api/events/*/reject',
        'api/vacation/cancel/*',
        'api/parking/*'
    ];

    /**
     * Determine if the request has a valid CSRF token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        // Debug-Logging für CSRF-Token
        \Illuminate\Support\Facades\Log::info('CSRF Token Check', [
            'token' => $request->input('_token'),
            'header' => $request->header('X-CSRF-TOKEN'),
            'header_xsrf' => $request->header('X-XSRF-TOKEN'),
            'cookie' => $request->cookie('XSRF-TOKEN'),
            'session_token' => $request->session()->token(),
            'uri' => $request->path(),
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'user_agent' => $request->header('User-Agent'),
            'referer' => $request->header('Referer'),
        ]);

        // Wenn die Anfrage von einem authentifizierten Benutzer kommt und ein CSRF-Token im Header vorhanden ist,
        // aber kein Token im Cookie, dann setzen wir das Token im Cookie
        if ($request->user() && $request->header('X-CSRF-TOKEN') && !$request->cookie('XSRF-TOKEN')) {
            \Illuminate\Support\Facades\Log::info('Setting XSRF-TOKEN cookie for authenticated user');
            cookie('XSRF-TOKEN', $request->session()->token(), 120, null, null, false, false);
        }

        return parent::tokensMatch($request);
    }
}

