<?php

namespace App\Http\Controllers;

use App\Models\SocialPost;
use App\Models\PlatformAccount;
use App\Models\PostPlatform;
use App\Models\PostMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SocialPostController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $posts = SocialPost::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(20)
            ->get(['id', 'caption', 'hashtags', 'status', 'publish_type', 'timezone', 'scheduled_at', 'published_at', 'created_at'])
            ->map(function (SocialPost $p) {
                return [
                    'id' => $p->id,
                    'caption' => $p->caption,
                    'hashtags' => $p->hashtags,
                    'status' => $p->status,
                    'publish_type' => $p->publish_type,
                    'timezone' => $p->timezone,
                    'scheduled_at' => optional($p->scheduled_at)->toIso8601String(),
                    'published_at' => optional($p->published_at)->toIso8601String(),
                    'created_at' => optional($p->created_at)->toIso8601String(),
                ];
            });

        $accounts = PlatformAccount::query()
            ->where('user_id', $user->id)
            ->orderBy('platform')
            ->get(['id','platform','account_username','is_connected','stats'])
            ->map(function (PlatformAccount $a) {
                $followers = (int) ((array) ($a->stats ?? []))['followers'] ?? null;
                return [
                    'id' => $a->id,
                    'platform' => $a->platform,
                    'account_username' => $a->account_username,
                    'connected' => (bool) $a->is_connected,
                    'followers' => $followers,
                ];
            });

        return Inertia::render('content-creator', [
            'posts' => $posts,
            'accounts' => $accounts,
            'mode' => 'index',
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $accounts = PlatformAccount::query()
            ->where('user_id', $user->id)
            ->orderBy('platform')
            ->get(['id','platform','account_username','is_connected','stats'])
            ->map(function (PlatformAccount $a) {
                $followers = (int) ((array) ($a->stats ?? []))['followers'] ?? null;
                return [
                    'id' => $a->id,
                    'platform' => $a->platform,
                    'account_username' => $a->account_username,
                    'connected' => (bool) $a->is_connected,
                    'followers' => $followers,
                ];
            });

        return Inertia::render('content-creator', [
            'posts' => [],
            'accounts' => $accounts,
            'mode' => 'create',
        ]);
    }

    public function storeDraft(Request $request)
    {
        $user = $request->user();
        $post = new SocialPost();
        $post->user_id = $user->id;
        $post->status = 'draft';
        $post->publish_type = 'scheduled';
        $post->timezone = $request->string('timezone', 'UTC');
        $post->save();

        return response()->json(['id' => $post->id]);
    }

    public function updateDraft(Request $request, SocialPost $post)
    {
        $this->authorizePost($request, $post);
        $data = $request->validate([
            'caption' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);
        $post->fill($data);
        $post->save();
        return response()->json(['ok' => true]);
    }

    public function savePlatforms(Request $request, SocialPost $post)
    {
        $this->authorizePost($request, $post);
        $ids = $request->input('platform_account_ids', []);
        if (!is_array($ids)) $ids = [];

        DB::transaction(function () use ($post, $ids) {
            PostPlatform::where('post_id', $post->id)->delete();
            foreach ($ids as $pid) {
                PostPlatform::create([
                    'post_id' => $post->id,
                    'platform' => '',
                    'platform_account_id' => $pid,
                    'status' => 'scheduled',
                ]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function saveMedia(Request $request, SocialPost $post)
    {
        $this->authorizePost($request, $post);
        $items = $request->input('media', []); // [{id, position}]
        if (!is_array($items)) $items = [];
        DB::transaction(function () use ($post, $items) {
            PostMedia::where('post_id', $post->id)->delete();
            foreach ($items as $idx => $m) {
                $id = is_array($m) ? ($m['id'] ?? null) : $m;
                $pos = is_array($m) ? ($m['position'] ?? $idx) : $idx;
                if ($id) {
                    PostMedia::create([
                        'post_id' => $post->id,
                        'media_file_id' => $id,
                        'position' => (int) $pos,
                    ]);
                }
            }
        });
        return response()->json(['ok' => true]);
    }

    public function schedule(Request $request, SocialPost $post)
    {
        $this->authorizePost($request, $post);
        $data = $request->validate([
            'publish_type' => ['required', 'in:now,scheduled'],
            'scheduled_at' => ['nullable', 'date'],
        ]);
        $post->publish_type = $data['publish_type'];
        if ($data['publish_type'] === 'scheduled') {
            $post->scheduled_at = $data['scheduled_at'] ?? $post->scheduled_at;
            $post->status = 'scheduled';
        } else {
            $post->status = 'published';
            $post->published_at = now();
        }
        $post->save();
        return response()->json(['ok' => true]);
    }

    protected function authorizePost(Request $request, SocialPost $post): void
    {
        abort_unless($post->user_id === $request->user()->id, 403);
    }
}
