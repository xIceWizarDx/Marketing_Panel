<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Models\MediaTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MediaLibraryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = MediaFile::query()->where('user_id', $user->id);

        // Filters
        if ($type = $request->string('type')->toString()) {
            if ($type === 'image') $query->where('mime_type', 'like', 'image/%');
            elseif ($type === 'video') $query->where('mime_type', 'like', 'video/%');
            elseif ($type === 'gif') $query->where('mime_type', 'image/gif');
        }
        if ($q = $request->string('q')->toString()) {
            $query->where(function ($qbuilder) use ($q) {
                $qbuilder->where('name', 'like', "%$q%");
            });
        }
        if ($start = $request->date('start', null)) {
            $query->whereDate('uploaded_at', '>=', $start);
        }
        if ($end = $request->date('end', null)) {
            $query->whereDate('uploaded_at', '<=', $end);
        }
        if ($ratio = $request->string('ratio')->toString()) {
            // approximate ratio filters using MySQL expression
            if (in_array($ratio, ['1:1','16:9','9:16'])) {
                [$rw, $rh] = array_map('intval', explode(':', $ratio));
                $query->whereNotNull('width')->whereNotNull('height')
                    ->whereRaw('ABS((width / height) - (? / ?)) < 0.15', [$rw, $rh]);
            }
        }
        if ($tagId = $request->integer('tag')) {
            $query->join('media_file_tag', 'media_file_tag.media_file_id', '=', 'media_files.id')
                ->where('media_file_tag.tag_id', $tagId)
                ->select('media_files.*');
        }

        $mediaPaginator = $query->orderByDesc('uploaded_at')->paginate(24)->withQueryString();

        $media = collect($mediaPaginator->items())->map(function (MediaFile $m) {
            $url = $m->url;
            if (is_string($url) && str_starts_with($url, '/')) {
                $url = url($url);
            }
            return [
                'id' => $m->id,
                'name' => $m->name,
                'url' => $url,
                'mime_type' => $m->mime_type,
                'size_bytes' => (int) $m->size_bytes,
                'width' => $m->width,
                'height' => $m->height,
                'uploaded_at' => optional($m->uploaded_at)->toIso8601String(),
            ];
        });

        $tags = MediaTag::query()->orderBy('name')->get(['id', 'name']);

        // counts for sidebar
        $counts = [
            'images' => MediaFile::where('user_id', $user->id)->where('mime_type', 'like', 'image/%')->count(),
            'videos' => MediaFile::where('user_id', $user->id)->where('mime_type', 'like', 'video/%')->count(),
            'gifs' => MediaFile::where('user_id', $user->id)->where('mime_type', 'image/gif')->count(),
            'all' => MediaFile::where('user_id', $user->id)->count(),
        ];

        return Inertia::render('media-library', [
            'media' => $media,
            'tags' => $tags,
            'counts' => $counts,
            'pagination' => [
                'total' => $mediaPaginator->total(),
                'per_page' => $mediaPaginator->perPage(),
                'current_page' => $mediaPaginator->currentPage(),
                'last_page' => $mediaPaginator->lastPage(),
                'from' => $mediaPaginator->firstItem(),
                'to' => $mediaPaginator->lastItem(),
            ],
            'filters' => [
                'type' => $type ?? null,
                'q' => $q ?? null,
                'start' => $request->input('start'),
                'end' => $request->input('end'),
                'ratio' => $ratio ?? null,
                'tag' => $tagId ?: null,
            ],
        ]);
    }

    public function upload(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'files' => ['required'],
            'files.*' => ['file', 'max:51200', 'mimetypes:image/jpeg,image/png,image/gif,video/mp4,video/quicktime'],
        ]);

        $saved = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store('media/'.$user->id, ['disk' => 'public']);
            $url = Storage::disk('public')->url($path);
            if (is_string($url) && str_starts_with($url, '/')) {
                $url = url($url);
            }

            $width = null;
            $height = null;
            if (str_starts_with($file->getMimeType(), 'image/')) {
                try {
                    [$width, $height] = getimagesize($file->getRealPath());
                } catch (\Throwable $e) {
                }
            }

            $m = new MediaFile();
            $m->user_id = $user->id;
            $m->name = $file->getClientOriginalName();
            $m->url = $url;
            $m->mime_type = $file->getMimeType();
            $m->size_bytes = $file->getSize();
            $m->width = $width;
            $m->height = $height;
            $m->uploaded_at = now();
            $m->save();

            $saved[] = $m->only(['id', 'name', 'url']);
        }

        return response()->json(['uploaded' => $saved]);
    }

    public function list(Request $request)
    {
        $user = $request->user();
        $query = MediaFile::query()->where('user_id', $user->id);
        if ($request->string('type')->toString() === 'image') $query->where('mime_type', 'like', 'image/%');
        if ($request->string('type')->toString() === 'video') $query->where('mime_type', 'like', 'video/%');
        if ($q = $request->string('q')->toString()) $query->where('name', 'like', "%$q%");
        $per = max(1, min(60, (int) $request->integer('per', 36)));
        $items = $query->orderByDesc('uploaded_at')->take($per)->get(['id','name','url','mime_type','width','height'])
            ->map(function (MediaFile $m) {
                $url = $m->url;
                if (is_string($url) && str_starts_with($url, '/')) {
                    $url = url($url);
                }
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'url' => $url,
                    'mime_type' => $m->mime_type,
                    'width' => $m->width,
                    'height' => $m->height,
                ];
            });
        return response()->json(['data' => $items]);
    }

    public function bulkDelete(Request $request)
    {
        $user = $request->user();
        $ids = (array) $request->input('ids', []);
        if (empty($ids)) return response()->json(['deleted' => 0]);
        $count = MediaFile::where('user_id', $user->id)->whereIn('id', $ids)->count();
        MediaFile::where('user_id', $user->id)->whereIn('id', $ids)->delete();
        return response()->json(['deleted' => $count]);
    }

    public function bulkTag(Request $request)
    {
        $user = $request->user();
        $ids = (array) $request->input('ids', []);
        $name = trim((string) $request->input('tag'));
        if ($name === '' || empty($ids)) return response()->json(['tagged' => 0]);

        $tag = MediaTag::firstOrCreate(['name' => $name]);
        $files = MediaFile::where('user_id', $user->id)->whereIn('id', $ids)->get('id');
        $count = 0;
        foreach ($files as $f) {
            // attach ignoring duplicate
            \DB::table('media_file_tag')->updateOrInsert([
                'media_file_id' => $f->id,
                'tag_id' => $tag->id,
            ], []);
            $count++;
        }
        return response()->json(['tagged' => $count, 'tag' => ['id' => $tag->id, 'name' => $tag->name]]);
    }
}
