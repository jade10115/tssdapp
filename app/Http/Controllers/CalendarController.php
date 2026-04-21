<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\CalendarEventAttendee;
use App\Models\Division;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    public function divisions()
    {
        $divisions = Division::query()
            ->where('status', 1)
            ->orderBy('division')
            ->get(['id','division','color','status']);

        return response()->json(['data' => $divisions]);
    }

    public function employees()
    {
        $emps = UserProfile::query()
            ->with(['position:id,position'])
            ->orderBy('last_name')
            ->get(['user_id','first_name','middle_name','last_name','suffix','position_id','phone','division']);

        $data = $emps->map(function ($p) {
            $full = trim(
                ($p->first_name ?? '') . ' ' .
                ($p->middle_name ?? '') . ' ' .
                ($p->last_name ?? '') . ' ' .
                ($p->suffix ?? '')
            );

            return [
                'user_id' => (int)$p->user_id,
                'full_name' => $full !== '' ? $full : 'No Name',
                'position' => optional($p->position)->position ?? '',
                'phone' => $p->phone ?? '',
                'division' => $p->division ?? '',
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function feed(Request $request)
    {
        $start = Carbon::parse($request->query('start'));
        $end   = Carbon::parse($request->query('end'));

        $showEvents = (int)($request->query('showEvents', 1)) === 1;
        $showTupad  = (int)($request->query('showTupad', 1)) === 1;
        $divisionId = $request->query('division_id', 'ALL');

        $items = [];

        if ($showEvents) {
            $events = CalendarEvent::query()
                ->with('attendees')
                ->whereBetween('start_at', [$start, $end])
                ->orderBy('start_at')
                ->get();

            foreach ($events as $e) {
                $items[] = [
                    'id' => 'EVT-'.$e->id,
                    'type' => 'EVENT',
                    'title' => $e->title,
                    'start' => $e->start_at->toIso8601String(),
                    'end' => $e->end_at?->toIso8601String(),
                    'allDay' => (bool)$e->is_all_day,
                    'description' => $e->description,
                    'color' => '#2b6cb0',
                    'meta' => [
                        'event_id' => $e->id,
                        'attendees' => $e->attendees->pluck('user_id')->values(),
                    ],
                ];
            }
        }

        if ($showTupad) {
            $q = DB::table('tupad_adl_breakdowns as b')
                ->leftJoin('tupad_adl_details as d', 'd.id', '=', 'b.adl_detail_id')
                ->leftJoin('divisions as v', 'v.id', '=', 'd.division_id')
                ->select([
                    'b.id',
                    'b.lgu',
                    'b.osh_date',
                    'b.payout_date',
                    'd.division_id',
                    'v.division as division_name',
                    'v.color as division_color',
                ])
                ->where(function($w) use ($start, $end) {
                    $w->whereBetween('b.osh_date', [$start->toDateString(), $end->toDateString()])
                      ->orWhereBetween('b.payout_date', [$start->toDateString(), $end->toDateString()]);
                });

            if ($divisionId !== 'ALL') {
                $q->where('d.division_id', $divisionId);
            }

            $rows = $q->orderByRaw("COALESCE(b.osh_date, b.payout_date) asc")->get();

            foreach ($rows as $r) {
                $color = $r->division_color ?: '#718096';
                $divName = $r->division_name ?: 'No Division';

                if (!empty($r->osh_date)) {
                    $items[] = [
                        'id' => 'TPD-OSH-'.$r->id,
                        'type' => 'TUPAD',
                        'title' => "TUPAD OSH • {$divName} • {$r->lgu}",
                        'start' => Carbon::parse($r->osh_date)->startOfDay()->toIso8601String(),
                        'end' => Carbon::parse($r->osh_date)->endOfDay()->toIso8601String(),
                        'allDay' => true,
                        'description' => "OSH Date: {$r->osh_date}",
                        'color' => $color,
                        'meta' => [
                            'breakdown_id' => $r->id,
                            'division_id' => $r->division_id,
                            'division' => $divName,
                            'lgu' => $r->lgu,
                            'date_type' => 'OSH',
                        ],
                    ];
                }

                if (!empty($r->payout_date)) {
                    $items[] = [
                        'id' => 'TPD-PAYOUT-'.$r->id,
                        'type' => 'TUPAD',
                        'title' => "TUPAD PAYOUT • {$divName} • {$r->lgu}",
                        'start' => Carbon::parse($r->payout_date)->startOfDay()->toIso8601String(),
                        'end' => Carbon::parse($r->payout_date)->endOfDay()->toIso8601String(),
                        'allDay' => true,
                        'description' => "Payout Date: {$r->payout_date}",
                        'color' => $color,
                        'meta' => [
                            'breakdown_id' => $r->id,
                            'division_id' => $r->division_id,
                            'division' => $divName,
                            'lgu' => $r->lgu,
                            'date_type' => 'PAYOUT',
                        ],
                    ];
                }
            }
        }

        return response()->json(['data' => $items]);
    }

  public function storeEvent(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'start_at' => 'required|date',
        'end_at' => 'nullable|date|after_or_equal:start_at',
        'is_all_day' => 'nullable|boolean',
        'attendee_user_ids' => 'nullable|array',
        'attendee_user_ids.*' => 'integer',
    ]);

    $event = CalendarEvent::create([
        'title' => $validated['title'],
        'description' => $validated['description'] ?? null,
        'start_at' => Carbon::parse($validated['start_at']),
        'end_at' => isset($validated['end_at'])
            ? Carbon::parse($validated['end_at'])
            : null,
        'is_all_day' => (bool)($validated['is_all_day'] ?? false),
        'reminder_sent' => false,
    ]);

    // Save attendees
    $ids = array_values(array_unique(array_filter($validated['attendee_user_ids'] ?? [])));

    if (!empty($ids)) {
        $profiles = UserProfile::whereIn('user_id', $ids)
            ->get(['user_id', 'phone'])
            ->keyBy('user_id');

        $rows = [];
        foreach ($ids as $uid) {
            $rows[] = [
                'calendar_event_id' => $event->id,
                'user_id' => $uid,
                'phone' => $profiles[$uid]->phone ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        CalendarEventAttendee::insert($rows);
    }

    return response()->json([
        'success' => true,
        'message' => 'Event created successfully',
        'data' => $event->load('attendees'),
    ], 201);
}

    public function upcoming(Request $request)
    {
        $days = max(1, (int)$request->query('days', 7));
        $from = now();
        $to = now()->addDays($days);

        $events = CalendarEvent::query()
            ->whereBetween('start_at', [$from, $to])
            ->orderBy('start_at')
            ->limit(50)
            ->get(['id','title','start_at','end_at','is_all_day']);

        return response()->json(['data' => $events]);
    }


    public function send(string $phone, string $message): array
{
    $key = config('services.textbelt.key', 'textbelt');

    $res = Http::asForm()->post('https://textbelt.com/text', [
        'phone' => $phone,
        'message' => $message,
        'key' => $key,
    ]);

    \Log::info('Textbelt response', [
        'phone' => $phone,
        'response' => $res->json(),
    ]);

    return $res->json() ?? ['success' => false];
}

    }
