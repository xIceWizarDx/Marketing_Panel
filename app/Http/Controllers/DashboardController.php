<?php

namespace App\Http\Controllers;

use App\Models\PlatformAccount;
use App\Models\SocialPost;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $accounts = PlatformAccount::query()
            ->where('user_id', $user->id)
            ->orderBy('platform')
            ->get(['id','platform','account_username','account_email','connection_status','is_connected','last_sync_at','stats']);

        // Derive simple metrics from stats JSON if available
        $totalFollowers = 0;
        $monthlyReach = 0;
        $engRates = [];
        foreach ($accounts as $a) {
            $stats = (array) ($a->stats ?? []);
            $totalFollowers += (int) ($stats['followers'] ?? 0);
            $monthlyReach += (int) ($stats['monthly_reach'] ?? 0);
            if (isset($stats['engagement_rate'])) {
                $engRates[] = (float) $stats['engagement_rate'];
            }
        }
        $engagementRate = count($engRates) ? array_sum($engRates) / count($engRates) : 0.0;

        $scheduledPosts = SocialPost::query()->where('user_id', $user->id)->where('status','scheduled')->count();

        $recent = SocialPost::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(5)
            ->get(['id','caption','status','created_at','published_at']);

        return Inertia::render('dashboard', [
            'greetingName' => $user->name,
            'platforms' => $accounts->map(function ($a) {
                return [
                    'id' => $a->id,
                    'platform' => $a->platform,
                    'username' => $a->account_username ?? $a->account_email,
                    'status' => $a->connection_status,
                    'is_connected' => (bool) $a->is_connected,
                    'last_sync_at' => optional($a->last_sync_at)->diffForHumans(),
                    'stats' => $a->stats,
                ];
            }),
            'metrics' => [
                'total_followers' => $totalFollowers,
                'engagement_rate' => round($engagementRate, 2),
                'scheduled_posts' => $scheduledPosts,
                'monthly_reach' => $monthlyReach,
            ],
            'recent' => $recent->map(function ($p) {
                return [
                    'id' => $p->id,
                    'caption' => $p->caption,
                    'status' => $p->status,
                    'created_at' => optional($p->created_at)->toIso8601String(),
                    'published_at' => optional($p->published_at)->toIso8601String(),
                ];
            }),
        ]);
    }
}

