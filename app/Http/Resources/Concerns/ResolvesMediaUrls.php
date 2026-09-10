<?php

namespace App\Http\Resources\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resources using this trait expose stored media as absolute URLs so the
 * front end can drop the value straight into an `<img src>` without knowing
 * where the files live.
 */
trait ResolvesMediaUrls
{
    /**
     * Resolve a stored media path to an absolute URL.
     *
     * Paths are resolved against the `public` disk, which uploads are written
     * to. Values that are already absolute URLs (the placeholder images used
     * by the factories, for example) are passed through untouched.
     */
    protected function mediaUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
