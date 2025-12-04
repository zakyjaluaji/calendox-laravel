<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Participant;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    private function loadToken(): ?array
    {
        if (\Storage::disk('local')->exists('google-token.json')) {
            $json = \Storage::disk('local')->get('google-token.json');
            $data = json_decode($json, true);
            if (is_array($data)) return $data;
        }
        return null;
    }

    public function one(Request $r)
    {
        if (!class_exists(\Google_Client::class)) return response()->json(['success' => false, 'message' => 'Google API client missing']);
        $appointmentId = (int)($r->input('appointment_id') ?? $r->query('appointment_id'));
        if ($appointmentId <= 0) return response()->json(['success' => false, 'message' => 'appointment_id tidak valid']);
        $ap = Appointment::find($appointmentId);
        if (!$ap) return response()->json(['success' => false, 'message' => 'Appointment tidak ditemukan']);

        $client = new \Google_Client();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->setRedirectUri(route('google.callback'));
        $client->addScope(\Google\Service\Calendar::CALENDAR);
        $token = $this->loadToken();
        if (!$token) return response()->json(['success' => false, 'message' => 'Belum terhubung ke Google']);
        $client->setAccessToken($token);
        if ($client->isAccessTokenExpired()) {
            $refreshToken = $client->getRefreshToken();
            if ($refreshToken) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                \Storage::disk('local')->put('google-token.json', json_encode($newToken));
                $client->setAccessToken($newToken);
            } else {
                return response()->json(['success' => false, 'message' => 'Token kadaluarsa dan tidak ada refresh token']);
            }
        }
        $service = new \Google\Service\Calendar($client);

        $sRFC = (new \DateTime($ap->start_date.' '.$ap->start_time))->format(\DateTime::RFC3339);
        $eRFC = (new \DateTime($ap->end_date.' '.$ap->end_time))->format(\DateTime::RFC3339);
        $participants = Participant::query()->where('appointment_id', $ap->id)->get()->map(function ($p) {
            return ['email' => $p->email, 'displayName' => $p->name];
        })->filter(fn($x) => !empty($x['email']))->values()->all();

        $attachmentUrl = $ap->attachment_filename ? url('storage/uploads/appointments/'.$ap->id.'/'.$ap->attachment_filename) : '';
        $descParts = [];
        if (!empty($ap->pic_name)) $descParts[] = 'PIC: '.$ap->pic_name;
        if (!empty($attachmentUrl)) $descParts[] = 'Lampiran: '.$attachmentUrl;
        $description = !empty($descParts) ? implode("\n", $descParts) : null;

        $event = new \Google\Service\Calendar\Event([
            'summary' => $ap->title ?? 'Untitled',
            'description' => $description,
            'start' => ['dateTime' => $sRFC, 'timeZone' => config('google.timezone')],
            'end' => ['dateTime' => $eRFC, 'timeZone' => config('google.timezone')],
            'attendees' => $participants,
        ]);

        try {
            if (!empty($ap->google_event_id)) {
                $updated = $service->events->update(config('google.calendar_id'), $ap->google_event_id, $event, ['sendUpdates' => 'none']);
                return response()->json(['success' => true, 'message' => 'Event Google diperbarui', 'google_event_id' => $updated->id]);
            } else {
                $created = $service->events->insert(config('google.calendar_id'), $event, ['sendUpdates' => 'none']);
                $ap->google_event_id = $created->id;
                $ap->save();
                return response()->json(['success' => true, 'message' => 'Event Google dibuat', 'google_event_id' => $created->id]);
            }
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal sinkron: '.$e->getMessage()]);
        }
    }

    public function twoWay()
    {
        if (!class_exists(\Google_Client::class)) return response()->json(['success' => false, 'message' => 'Google API client missing']);
        $client = new \Google_Client();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->setRedirectUri(route('google.callback'));
        $client->addScope(\Google\Service\Calendar::CALENDAR);
        $token = $this->loadToken();
        if (!$token) return response()->json(['success' => false, 'message' => 'Belum terhubung ke Google']);
        $client->setAccessToken($token);
        if ($client->isAccessTokenExpired()) {
            $refreshToken = $client->getRefreshToken();
            if ($refreshToken) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                if (isset($newToken['error'])) return response()->json(['success' => false, 'message' => 'Refresh token tidak valid']);
                \Storage::disk('local')->put('google-token.json', json_encode($newToken));
                $client->setAccessToken($newToken);
            } else {
                return response()->json(['success' => false, 'message' => 'Token kadaluarsa dan tidak ada refresh token']);
            }
        }
        $service = new \Google\Service\Calendar($client);

        date_default_timezone_set(config('google.timezone'));
        $now = new \DateTime('now');
        $min = (clone $now)->modify('-1 month');
        $max = (clone $now)->modify('+1 month');
        $timeMin = $min->format(\DateTime::RFC3339);
        $timeMax = $max->format(\DateTime::RFC3339);

        $minDate = $min->format('Y-m-d');
        $maxDate = $max->format('Y-m-d');

        $locals = Appointment::query()->where('end_date', '>=', $minDate)->where('start_date', '<=', $maxDate)->get();
        $pushedCount = 0;
        foreach ($locals as $ap) {
            if (empty($ap->start_date) || empty($ap->start_time) || empty($ap->end_date) || empty($ap->end_time)) continue;
            $sRFC = (new \DateTime($ap->start_date.' '.$ap->start_time))->format(\DateTime::RFC3339);
            $eRFC = (new \DateTime($ap->end_date.' '.$ap->end_time))->format(\DateTime::RFC3339);
            $participants = Participant::query()->where('appointment_id', $ap->id)->get()->map(function ($p) {
                return ['email' => $p->email, 'displayName' => $p->name];
            })->filter(fn($x) => !empty($x['email']))->values()->all();
            $attachmentUrl = $ap->attachment_filename ? url('storage/uploads/appointments/'.$ap->id.'/'.$ap->attachment_filename) : '';
            $descParts = [];
            if (!empty($ap->pic_name)) $descParts[] = 'PIC: '.$ap->pic_name;
            if (!empty($attachmentUrl)) $descParts[] = 'Lampiran: '.$attachmentUrl;
            $description = !empty($descParts) ? implode("\n", $descParts) : null;
            $event = new \Google\Service\Calendar\Event([
                'summary' => $ap->title ?? 'Untitled',
                'description' => $description,
                'start' => ['dateTime' => $sRFC, 'timeZone' => config('google.timezone')],
                'end' => ['dateTime' => $eRFC, 'timeZone' => config('google.timezone')],
                'attendees' => $participants,
            ]);
            try {
                if (!empty($ap->google_event_id)) {
                    $updated = $service->events->update(config('google.calendar_id'), $ap->google_event_id, $event, ['sendUpdates' => 'none']);
                    if ($updated && $updated->id) $pushedCount++;
                } else {
                    $created = $service->events->insert(config('google.calendar_id'), $event, ['sendUpdates' => 'none']);
                    $ap->google_event_id = $created->id;
                    $ap->save();
                    if ($created->id) $pushedCount++;
                }
            } catch (\Throwable $e) {}
        }

        $optParams = ['timeMin' => $timeMin, 'timeMax' => $timeMax, 'singleEvents' => true, 'orderBy' => 'startTime'];
        try {
            $events = $service->events->listEvents(config('google.calendar_id'), $optParams);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menarik event dari Google: '.$e->getMessage()]);
        }
        $imported = 0;
        $participantsImported = 0;

        foreach ($events->getItems() as $item) {
            $gid = $item->getId();
            $summary = $item->getSummary();
            $start = $item->getStart();
            $end   = $item->getEnd();
            $startDate = '';
            $startTime = '';
            $endDate = '';
            $endTime = '';
            if ($start->getDateTime()) {
                $sdt = new \DateTime($start->getDateTime());
                $startDate = $sdt->format('Y-m-d');
                $startTime = $sdt->format('H:i');
            } else {
                $startDate = $start->getDate();
                $startTime = '00:00';
            }
            if ($end->getDateTime()) {
                $edt = new \DateTime($end->getDateTime());
                $endDate = $edt->format('Y-m-d');
                $endTime = $edt->format('H:i');
            } else {
                $endExclusive = new \DateTime($end->getDate());
                $endExclusive->modify('-1 day');
                $endDate = $endExclusive->format('Y-m-d');
                $endTime = '23:59';
            }
            $exists = Appointment::query()->where('google_event_id', $gid)->first();
            if ($exists) {
                $appointmentId = $exists->id;
            } else {
                $title = $summary ?: 'Untitled';
                $ap = Appointment::create(['title' => $title, 'pic_name' => '', 'start_date' => $startDate, 'end_date' => $endDate, 'start_time' => $startTime, 'end_time' => $endTime, 'google_event_id' => $gid]);
                $imported++;
                $appointmentId = $ap->id;
            }
            if (!empty($appointmentId)) {
                $attendees = $item->getAttendees();
                if (is_array($attendees)) {
                    foreach ($attendees as $att) {
                        $name = is_array($att) ? ($att['displayName'] ?? '') : (method_exists($att, 'getDisplayName') ? $att->getDisplayName() : '');
                        $email = is_array($att) ? ($att['email'] ?? '') : (method_exists($att, 'getEmail') ? $att->getEmail() : '');
                        if ($email) {
                            $dup = Participant::query()->where('appointment_id', $appointmentId)->where('email', $email)->exists();
                            if (!$dup) {
                                Participant::create(['appointment_id' => $appointmentId, 'name' => $name, 'email' => $email]);
                                $participantsImported++;
                            }
                        }
                    }
                }
            }
        }
        return response()->json(['success' => true, 'message' => 'Sinkron dua arah selesai.', 'imported' => $imported, 'participants_imported' => $participantsImported, 'pushed_count' => $pushedCount]);
    }
}
