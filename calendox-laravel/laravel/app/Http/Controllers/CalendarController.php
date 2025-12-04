<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class CalendarController extends Controller
{
    public function index()
    {
        $rows = Appointment::query()->get();
        $events = [];
        foreach ($rows as $row) {
            $s = new \DateTime($row->start_date);
            $e = new \DateTime($row->end_date);
            while ($s <= $e) {
                $events[] = [
                    'id' => $row->id,
                    'title' => ($row->title.' - '.$row->pic_name),
                    'date' => $s->format('Y-m-d'),
                    'start' => $row->start_date,
                    'end' => $row->end_date,
                    'start_time' => $row->start_time,
                    'end_time' => $row->end_time,
                    'color' => $row->color,
                    'attachment_filename' => $row->attachment_filename,
                    'attachment_url' => $row->attachment_filename ? url('storage/uploads/appointments/'.$row->id.'/'.$row->attachment_filename) : '',
                ];
                $s->modify('+1 day');
            }
        }
        $googleConnected = Storage::disk('local')->exists('google-token.json');
        return View::make('calendar', ['eventsFromDB' => $events, 'googleConnected' => $googleConnected, 'role' => 'Pengelola', 'isAdmin' => true]);
    }
}
