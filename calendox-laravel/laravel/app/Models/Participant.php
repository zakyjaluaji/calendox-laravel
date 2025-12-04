<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $table = 'participant';
    public $timestamps = false;
    protected $fillable = ['appointment_id','name','email'];
}

