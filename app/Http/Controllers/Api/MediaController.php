<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Http\Requests\Media\UpdateMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = Media::query()->with('uploader');

        if ($search = $request->input('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('original_name', 'like', "%{$search}%")
                    ->orWhere('caption', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }

        if ($uploader = $request->integer('user_id')) {
            $query->where('user_id', $uploader);
        }

        if ($mime = $request->input('mime')) {
            $query->where('mime_type', 'like', "{$mime}%");
        }

        $perPage = (int) min(max($request->integer('per_page', 15) ?: 15, 1), 100);

        $media = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        return MediaResource::collection($media);
    }

    public function store(StoreMediaRequest $request)
    {
        $file = $request->file('file');
        $disk = 'public';
        $path = $file->store('media', $disk);

        $media = Media::create([
            'user_id' => $request->user()->id ?? null,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extension' => Str::lower($file->getClientOriginalExtension()),
            'alt_text' => $request->input('alt_text'),
            'caption' => $request->input('caption'),
        ]);

        $media->load('uploader');

        return MediaResource::make($media)->response()->setStatusCode(201);
    }

    public function update(UpdateMediaRequest $request, Media $media)
    {
        $data = $request->validated();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $disk = $media->disk ?? 'public';
            $newPath = $file->store('media', $disk);

            if ($media->path) {
                Storage::disk($disk)->delete($media->path);
            }

            $media->fill([
                'path' => $newPath,
                'disk' => $disk,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'extension' => Str::lower($file->getClientOriginalExtension()),
            ]);
        }

        if (array_key_exists('alt_text', $data)) {
            $media->alt_text = $data['alt_text'];
        }

        if (array_key_exists('caption', $data)) {
            $media->caption = $data['caption'];
        }

        $media->save();

        return MediaResource::make($media->fresh()->load('uploader'));
    }

    public function show(Media $media)
    {
        return MediaResource::make($media->load('uploader'));
    }

    public function destroy(Media $media)
    {
        if ($media->path) {
            Storage::disk($media->disk ?? 'public')->delete($media->path);
        }

        $media->delete();

        return response()->json([
            'message' => 'Media deleted.',
        ]);
    }
}
