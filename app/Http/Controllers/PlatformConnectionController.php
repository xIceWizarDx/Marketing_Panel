<?php

namespace App\Http\Controllers;

use App\Models\PlatformAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlatformConnectionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $accounts = PlatformAccount::query()
            ->where('user_id', $user->id)
            ->orderBy('platform')
            ->get([
                'id', 'platform', 'provider_account_id', 'account_username', 'account_email', 'connection_status', 'is_connected', 'last_sync_at', 'stats', 'created_at', 'updated_at',
            ])
            ->map(function (PlatformAccount $a) {
                return [
                    'id' => $a->id,
                    'platform' => $a->platform,
                    'provider_account_id' => $a->provider_account_id,
                    'account_username' => $a->account_username,
                    'account_email' => $a->account_email,
                    'connection_status' => $a->connection_status,
                    'is_connected' => (bool) $a->is_connected,
                    'last_sync_at' => optional($a->last_sync_at)->toIso8601String(),
                    'stats' => $a->stats,
                ];
            });

        $statusCounts = [
            'connected' => $accounts->where('connection_status', 'connected')->count(),
            'expired' => $accounts->where('connection_status', 'expired')->count(),
            'revoked' => $accounts->where('connection_status', 'revoked')->count(),
            'error' => $accounts->where('connection_status', 'error')->count(),
        ];

        return Inertia::render('platform-connections', [
            'accounts' => $accounts,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function updateStatus(Request $request, \App\Models\PlatformAccount $account)
    {
        abort_unless($request->user()->id === $account->user_id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:connected,expired,revoked,error'],
        ]);

        $account->connection_status = $data['status'];
        $account->is_connected = $data['status'] === 'connected';
        $account->last_sync_at = $account->is_connected ? now() : $account->last_sync_at;
        $account->save();

        return response()->json([
            'id' => $account->id,
            'connection_status' => $account->connection_status,
            'is_connected' => (bool) $account->is_connected,
            'last_sync_at' => optional($account->last_sync_at)->toIso8601String(),
        ]);
    }
}
