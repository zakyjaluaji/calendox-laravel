<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppointmentController extends Controller
{
    private array $palette = ['#3b82f6','#1e3a8a','#6366f1','#8b5cf6','#14b8a6','#10b981','#f59e0b','#f43f5e','#ef4444','#64748b'];

    public function store(Request $r)
    {
        $color = strtolower((string)$r->input('color'));
        if (!in_array($color, array_map('strtolower', $this->palette), true)) $color = $this->palette[0];

        $ap = Appointment::create([
            'title' => trim((string)$r->input('title')),
            'pic_name' => trim((string)$r->input('pic_name')),
            'start_date' => $r->input('start_date'),
            'end_date' => $r->input('end_date'),
            'start_time' => $r->input('start_time'),
            'end_time' => $r->input('end_time'),
            'color' => $color,
        ]);

        if ($r->hasFile('attachment')) {
            $file = $r->file('attachment');
            if ($file->isValid() && strtolower($file->getClientOriginalExtension()) === 'pdf' && $file->getSize() <= 5*1024*1024) {
                $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.pdf';
                $path = 'uploads/appointments/'.$ap->id;
                Storage::disk('public')->makeDirectory($path);
                $file->storeAs($path, $safe, 'public');
                $ap->attachment_filename = $safe;
                $ap->save();
            }
        }
        return redirect()->route('calendar')->with('success', 1);
    }

    public function update($id, Request $r)
    {
        $ap = Appointment::findOrFail($id);
        $color = strtolower((string)$r->input('color'));
        if (!in_array($color, array_map('strtolower', $this->palette), true)) $color = $this->palette[0];

        $ap->fill([
            'title' => trim((string)$r->input('title')),
            'pic_name' => trim((string)$r->input('pic_name')),
            'start_date' => $r->input('start_date'),
            'end_date' => $r->input('end_date'),
            'start_time' => $r->input('start_time'),
            'end_time' => $r->input('end_time'),
            'color' => $color,
        ])->save();

        if ($r->hasFile('attachment')) {
            $file = $r->file('attachment');
            if ($file->isValid() && strtolower($file->getClientOriginalExtension()) === 'pdf' && $file->getSize() <= 5*1024*1024) {
                $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.pdf';
                $path = 'uploads/appointments/'.$ap->id;
                Storage::disk('public')->makeDirectory($path);
                $file->storeAs($path, $safe, 'public');
                if ($ap->attachment_filename && $ap->attachment_filename !== $safe) {
                    Storage::disk('public')->delete($path.'/'.$ap->attachment_filename);
                }
                $ap->attachment_filename = $safe;
                $ap->save();
            }
        }
        return redirect()->route('calendar')->with('success', 2);
    }

    public function destroy($id)
    {
        $ap = Appointment::findOrFail($id);

        try {
            if (!empty($ap->google_event_id) && class_exists(\Google_Client::class)) {
                $client = new \Google_Client();
                $client->setClientId(config('google.client_id'));
                $client->setClientSecret(config('google.client_secret'));
                $client->setRedirectUri(route('google.callback'));
                $client->addScope(\Google\Service\Calendar::CALENDAR);

                $tokenJson = \Storage::disk('local')->exists('google-token.json') ? \Storage::disk('local')->get('google-token.json') : null;
                $token = $tokenJson ? json_decode($tokenJson, true) : null;
                if (is_array($token)) {
                    $client->setAccessToken($token);
                    if ($client->isAccessTokenExpired()) {
                        $refreshToken = $client->getRefreshToken();
                        if ($refreshToken) {
                            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                            if (is_array($newToken)) {
                                \Storage::disk('local')->put('google-token.json', json_encode($newToken));
                                $client->setAccessToken($newToken);
                            }
                        }
                    }
                    $service = new \Google\Service\Calendar($client);
                    try { $service->events->delete(config('google.calendar_id'), $ap->google_event_id); } catch (\Throwable $ge) {}
                }
            }
        } catch (\Throwable $e) {}

        $ap->delete();
        return redirect()->route('calendar')->with('success', 3);
    }
}
