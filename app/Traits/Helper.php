<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

use function Laravel\Prompts\info;

trait Helper
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    const MEDIA_PATH = 'media/';
    public function storeMedia($file) : string
    {
        if (!Storage::disk('public')->exists(self::MEDIA_PATH)) {
            Storage::disk('public')->makeDirectory(self::MEDIA_PATH);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        // correctly store the uploaded file with a specific filename and private visibility
        // Storage::disk('public')->putFileAs(self::MEDIA_PATH, $file, $filename, ['visibility' => 'private']);
        Storage::disk('local')->putFileAs(self::MEDIA_PATH, $file, $filename);

        // optional metadata
        Storage::disk('local')->put(self::MEDIA_PATH . $filename . '.meta', json_encode([
            'original_name' => $file->getClientOriginalName(),
            'uploaded_at' => now()->toDateTimeString(),
            'uploader_id' => Auth::id(),
        ]));

        return self::MEDIA_PATH . $filename;
    }

    public function getImageUrl($storedName) : string
    {
        // return storage::disk('local')->url($storedName);
        return Storage::disk('local')->temporaryUrl($storedName, now()->addMinutes(1));
    }
}
