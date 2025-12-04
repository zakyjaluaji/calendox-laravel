<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    public function add(Request $r)
    {
        $eventId = (int)$r->input('event_id');
        $name = trim((string)$r->input('name'));
        $email = trim((string)$r->input('email'));
        if ($eventId <= 0 || $name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'Input tidak valid.'], 200);
        }
        $exists = Participant::query()->where('appointment_id', $eventId)->where('email', $email)->exists();
        if ($exists) return response()->json(['success' => false, 'message' => 'Peserta duplikat.'], 200);
        $p = Participant::create(['appointment_id' => $eventId, 'name' => $name, 'email' => $email]);
        return response()->json(['success' => true, 'message' => 'Peserta berhasil disimpan.', 'participant_id' => $p->id], 200);
    }

    public function list(Request $r)
    {
        $appointmentId = (int)$r->input('appointment_id');
        if ($appointmentId <= 0) return response()->json(['success' => false, 'message' => 'Parameter tidak valid.'], 200);
        $rows = Participant::query()->select('id','name','email','created_at')->where('appointment_id', $appointmentId)->orderByDesc('created_at')->get();
        return response()->json(['success' => true, 'data' => $rows], 200);
    }

    public function delete(Request $r)
    {
        $pid = (int)$r->input('participant_id');
        if ($pid <= 0) return response()->json(['success' => false, 'message' => 'Parameter tidak valid.'], 200);
        Participant::query()->where('id', $pid)->delete();
        return response()->json(['success' => true, 'message' => 'Peserta dihapus.'], 200);
    }

    public function invite(Request $r)
    {
        $appointmentId = (int)$r->input('appointment_id');
        $participantId = (int)$r->input('participant_id');
        if ($appointmentId <= 0 || $participantId <= 0) return response()->json(['success' => false, 'message' => 'Parameter tidak valid.'], 200);
        $ap = Appointment::find($appointmentId);
        $p = Participant::find($participantId);
        if (!$ap || !$p) return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 200);
        if (!class_exists(\Google_Client::class)) {
            return response()->json(['success' => false, 'message' => 'Integrasi Google belum tersedia.'], 200);
        }
        $client = new \Google_Client();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->setRedirectUri(route('google.callback'));
        $client->addScope(\Google\Service\Calendar::CALENDAR);
        $tokenJson = \Storage::disk('local')->exists('google-token.json') ? \Storage::disk('local')->get('google-token.json') : null;
        $token = $tokenJson ? json_decode($tokenJson, true) : null;
        if (!is_array($token)) {
            return response()->json(['success' => false, 'message' => 'Belum terhubung ke Google.'], 200);
        }
        $client->setAccessToken($token);
        if ($client->isAccessTokenExpired()) {
            $refreshToken = $client->getRefreshToken();
            if ($refreshToken) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                if (!is_array($newToken) || isset($newToken['error'])) {
                    return response()->json(['success' => false, 'message' => 'Token Google tidak valid.'], 200);
                }
                \Storage::disk('local')->put('google-token.json', json_encode($newToken));
                $client->setAccessToken($newToken);
            } else {
                return response()->json(['success' => false, 'message' => 'Token Google kedaluwarsa.'], 200);
            }
        }

        try {
            $service = new \Google\Service\Calendar($client);
            if (!empty($ap->google_event_id)) {
                $current = $service->events->get(config('google.calendar_id'), $ap->google_event_id);
                $existing = [];
                $curAtts = $current->getAttendees();
                if (is_array($curAtts)) {
                    foreach ($curAtts as $a) {
                        $em = is_array($a) ? ($a['email'] ?? '') : (method_exists($a, 'getEmail') ? $a->getEmail() : '');
                        $nm = is_array($a) ? ($a['displayName'] ?? '') : (method_exists($a, 'getDisplayName') ? $a->getDisplayName() : '');
                        if ($em) $existing[strtolower($em)] = ['email' => $em, 'displayName' => $nm];
                    }
                }
                $k = strtolower($p->email);
                if ($k && !isset($existing[$k])) $existing[$k] = ['email' => $p->email, 'displayName' => $p->name];
                $event = new \Google\Service\Calendar\Event(['attendees' => array_values($existing)]);
                $service->events->patch(config('google.calendar_id'), $ap->google_event_id, $event, ['sendUpdates' => 'all']);
            } else {
                $sRFC = (new \DateTime($ap->start_date.' '.$ap->start_time))->format(\DateTime::RFC3339);
                $eRFC = (new \DateTime($ap->end_date.' '.$ap->end_time))->format(\DateTime::RFC3339);
                $attachmentUrl = $ap->attachment_filename ? url('storage/uploads/appointments/'.$ap->id.'/'.$ap->attachment_filename) : '';
                $descParts = [];
                if (!empty($ap->pic_name)) $descParts[] = 'PIC: '.$ap->pic_name;
                if (!empty($attachmentUrl)) $descParts[] = 'Lampiran: '.$attachmentUrl;
                $description = !empty($descParts) ? implode("\n", $descParts) : null;
                $participants = Participant::query()->where('appointment_id', $ap->id)->get()->map(function ($row) {
                    return ['email' => $row->email, 'displayName' => $row->name];
                })->filter(fn($x) => !empty($x['email']))->values()->all();
                $found = false; $key = strtolower($p->email);
                foreach ($participants as $it) { if (strtolower($it['email']) === $key) { $found = true; break; } }
                if (!$found && $p->email) $participants[] = ['email' => $p->email, 'displayName' => $p->name];
                $event = new \Google\Service\Calendar\Event([
                    'summary' => $ap->title ?? 'Untitled',
                    'description' => $description,
                    'start' => ['dateTime' => $sRFC, 'timeZone' => config('google.timezone')],
                    'end' => ['dateTime' => $eRFC, 'timeZone' => config('google.timezone')],
                    'attendees' => $participants,
                ]);
                $created = $service->events->insert(config('google.calendar_id'), $event, ['sendUpdates' => 'all']);
                $ap->google_event_id = $created->id; $ap->save();
            }
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengirim undangan.'], 200);
        }
        return response()->json(['success' => true, 'message' => 'Undangan dikirim.'], 200);
    }

    public function searchPic(Request $r)
    {
        $term = trim((string)$r->input('search'));
        if ($term === '') return response()->json([]);
        $out = [];
        $push = function($name,$email) use (&$out){
            $key = strtolower(trim($email ?: ''));
            if ($key === '') return;
            if (isset($out[$key])) return;
            $label = ($name ?: 'Tanpa Nama').' ('.($email ?: '-') .')';
            $out[$key] = ['value' => $email, 'label' => $label, 'name' => $name];
        };
        $users = User::query()->select('name','email')->where(function($w) use ($term){
            $w->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%');
        })->limit(8)->get();
        foreach ($users as $u) { $push($u->name, $u->email); }
        $parts = Participant::query()->select('name','email')->where(function($w) use ($term){
            $w->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%');
        })->groupBy('name','email')->limit(8)->get();
        foreach ($parts as $p) { $push($p->name, $p->email); }
        return response()->json(array_values($out));
    }
}
