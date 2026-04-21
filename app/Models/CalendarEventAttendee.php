<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEventAttendee extends Model
{
    protected $table = 'calendar_event_attendees';

    protected $fillable = ['calendar_event_id','user_id','phone'];
}
