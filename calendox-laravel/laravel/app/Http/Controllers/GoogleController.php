<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GoogleController extends Controller
{
    public function auth()
    {
        if (!class_exists(\Google_Client::class)) {
            return response('Google API Client not installed. Run: composer require google/apiclient:^2.15', 200);
        }
        $client = new \Google_Client();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->setRedirectUri(route('google.callback'));
        $client->addScope(\Google\Service\Calendar::CALENDAR);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        return redirect($client->createAuthUrl());
    }

    public function callback(Request $r)
    {
        if (!class_exists(\Google_Client::class)) {
            return response('Google API Client not installed. Run: composer require google/apiclient:^2.15', 200);
        }
        $code = $r->get('code');
        if (!$code) return response('Missing authorization code.', 200);
        $client = new \Google_Client();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->setRedirectUri(route('google.callback'));
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) return response('Error fetching access token: '.($token['error_description'] ?? $token['error']), 200);
        \Storage::disk('local')->put('google-token.json', json_encode($token));
        return response('<h3>Google Calendar connected successfully.</h3><p>Token saved. You can now sync events.</p><p><a href="'.route('calendar').'">Return to Calendar</a></p>', 200);
    }
}

