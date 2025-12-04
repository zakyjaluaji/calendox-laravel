<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'google_event_id','title','pic_name','start_date','end_date','start_time','end_time','attachment_filename','color'
    ];
}

