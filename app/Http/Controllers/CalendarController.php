<?php

namespace App\Http\Controllers;

use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $month = (int) $request->integer('month', now()->month);
        $year = (int) $request->integer('year', now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $posts = SocialPost::query()
            ->where('user_id', $user->id)
            ->whereBetween('scheduled_at', [$start, $end])
            ->orderBy('scheduled_at')
            ->get(['id', 'caption', 'status', 'scheduled_at']);

        $days = [];
        foreach (range(1, $start->daysInMonth) as $day) {
            $date = Carbon::createFromDate($year, $month, $day);
            $days[(string) $day] = [
                'date' => $date->toDateString(),
                'count' => 0,
                'items' => [],
            ];
        }
        foreach ($posts as $p) {
            if ($p->scheduled_at) {
                $d = Carbon::parse($p->scheduled_at);
                $key = (string) $d->day;
                $days[$key]['count']++;
                $days[$key]['items'][] = [
                    'id' => $p->id,
                    'caption' => $p->caption,
                    'status' => $p->status,
                    'time' => $d->format('H:i'),
                ];
            }
        }

        return Inertia::render('content-calendar', [
            'month' => $month,
            'year' => $year,
            'days' => $days,
        ]);
    }
}

