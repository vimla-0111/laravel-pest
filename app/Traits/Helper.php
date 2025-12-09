<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\ImageOptimizer\OptimizerChainFactory;

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

    public function storeMedia($file): string
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

        // compress image 
        $optimizerChain = OptimizerChainFactory::create();
        $optimizerChain->optimize(Storage::disk('local')->path(self::MEDIA_PATH . $filename)); // Optimizes the image in place
        // Image::load(self::MEDIA_PATH . $filename)
        //     ->optimize()
        //     ->save(self::MEDIA_PATH . $filename); // store new optimized file

        return self::MEDIA_PATH . $filename;
    }

    public function getImageUrl($storedName): string
    {
        return Storage::disk('local')->temporaryUrl($storedName, now()->addMinutes(1));
    }

    public function deleteMedia(?string $path): bool
    {
        if ($path) {
            return unlink(Storage::path($path));
        }
        return false;
    }

    public function deleteMediaFromStorage(?string $path): void
    {
        Storage::delete($path);
    }
}
