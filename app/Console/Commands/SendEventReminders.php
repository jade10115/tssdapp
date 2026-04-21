<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Services\Sms\TextbeltSms;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    protected $signature = 'calendar:send-reminders';
    protected $description = 'Send SMS reminders to attendees 1 day before the event';

    public function handle(TextbeltSms $sms)
    {
        $tomorrowStart = now()->addDay()->startOfDay();
        $tomorrowEnd   = now()->addDay()->endOfDay();

        $events = CalendarEvent::query()
            ->with('attendees')
            ->where('reminder_sent', false)
            ->whereBetween('start_at', [$tomorrowStart, $tomorrowEnd])
            ->get();

        foreach ($events as $event) {
            $time = Carbon::parse($event->start_at)->format('M d, Y h:i A');
            $deadline = $event->end_at ? Carbon::parse($event->end_at)->format('M d, Y h:i A') : 'N/A';

            $msg = "Upcoming Event: {$event->title}\nTime: {$time}\nDeadline: {$deadline}";

            foreach ($event->attendees as $att) {
                $phone = trim((string)($att->phone ?? ''));
                if ($phone === '') continue;

                $sms->send($phone, $msg);
            }

            $event->update([
                'reminder_sent' => true,
                'reminder_sent_at' => now(),
            ]);
        }

        $this->info('Reminders sent.');
        return 0;
    }
}
