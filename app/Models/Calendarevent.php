<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarEvent extends Model
{
    protected $table = 'calendar_events';

    protected $fillable = [
       'title','description','start_at','end_at','is_all_day',
        'reminder_sent','reminder_sent_at'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_all_day' => 'boolean',
        'reminder_sent' => 'boolean',
        'reminder_sent_at' => 'datetime',
    ];

    public function attendees(): HasMany
    {
        return $this->hasMany(CalendarEventAttendee::class, 'calendar_event_id');
    }
}
