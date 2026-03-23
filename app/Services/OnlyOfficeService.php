<?php

namespace App\Services;

use App\Support\OnlyOfficeJwt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class OnlyOfficeService
{
    public function getDisk(): string
    {
        return (string) config('onlyoffice.storage_disk', 'public');
    }

    public function normalizePath(string $path): string
    {
        return ltrim(rawurldecode($path), '/');
    }

    public function isDocx(string $path): bool
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'docx';
    }

    public function documentKey(string $path): string
    {
        $disk = $this->getDisk();

        $lastModified = (string) Storage::disk($disk)->lastModified($path);
        $size = (string) Storage::disk($disk)->size($path);

        return Str::limit(hash('sha256', implode('|', [$disk, $path, $lastModified, $size])), 128, '');
    }

    public function documentUrl(string $path): string
    {
        $disk = $this->getDisk();

        return Storage::disk($disk)->temporaryUrl(
            $path,
            now()->addMinutes((int) config('onlyoffice.document_url_ttl_minutes', 60))
        );
    }

    public function fileUrl(string $path): string
    {
        return URL::route('documents.file', ['path' => $path]);
    }

    public function callbackUrl(string $path): string
    {
        return URL::route('documents.callback', ['path' => $path]);
    }

    public function editorConfig(Request $request, string $path): array
    {
        $disk = $this->getDisk();

        return [
            'document' => [
                'fileType' => 'docx',
                'key' => $this->documentKey($path),
                'title' => basename($path),
                'url' => $this->fileUrl($path),
                'permissions' => [
                    'copy' => false,
                    'download' => false,
                    'edit' => true,
                    'print' => true,
                ],
            ],
            'documentType' => 'word',
            'editorConfig' => [
                'callbackUrl' => $this->callbackUrl($path),
                'lang' => str_replace('_', '-', app()->getLocale()),
                'mode' => 'edit',
                'user' => [
                    'id' => (string) ($request->user()?->getAuthIdentifier() ?? 'guest'),
                    'name' => $request->user()?->name ?? 'Guest',
                ],
                'customization' => [
                    'autosave' => true,
                    'compactHeader' => false,
                    'toolbarHideFileName' => false,
                ],
            ],
        ];
    }

    public function editorToken(array $config): string
    {
        $secret = (string) config('onlyoffice.jwt_secret');

        return OnlyOfficeJwt::encode($config, $secret);
    }

    public function ensureConfigured(): void
    {
        abort_unless(config('onlyoffice.doc_server_url'), 500, 'ONLYOFFICE_DOC_SERVER_URL is not configured.');
        abort_unless(config('onlyoffice.jwt_secret'), 500, 'ONLYOFFICE_JWT_SECRET is not configured.');
    }

    public function resolveSavePath(string $path, bool $saveAsNew = false): string
    {
        if (! $saveAsNew) {
            return $path;
        }

        $directory = pathinfo($path, PATHINFO_DIRNAME);
        $basename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        $newFilename = sprintf('%s-%s.%s', $basename, now()->format('YmdHis'), $extension);

        if ($directory === '.' || $directory === '') {
            return $newFilename;
        }

        return trim($directory, '/').'/'.$newFilename;
    }

    public function persistCallbackDocument(array $payload, string $sourcePath, bool $saveAsNew = false): string
    {
        $status = (int) ($payload['status'] ?? 0);

        if (! in_array($status, [2, 6], true)) {
            return $sourcePath;
        }

        if (empty($payload['url'])) {
            Log::warning('ONLYOFFICE callback missing download URL.', ['path' => $sourcePath, 'payload' => $payload]);

            abort(422, 'Callback missing download URL.');
        }

        $downloadUrl = $payload['url'];

        $http = Http::accept('application/octet-stream');

        if (! config('onlyoffice.verify_ssl', true)) {
            $http = $http->withoutVerifying();
        }

        $download = $http->get($downloadUrl);

        if (! $download->successful()) {
            Log::warning('ONLYOFFICE document download failed.', [
                'path' => $sourcePath,
                'status' => $download->status(),
                'url' => $downloadUrl,
            ]);

            abort(502, 'Document download failed.');
        }

        $savePath = $this->resolveSavePath($sourcePath, $saveAsNew);

        Storage::disk($this->getDisk())->put($savePath, $download->body());

        return $savePath;
    }

    public function localFileExists(string $path): bool
    {
        return Storage::disk($this->getDisk())->exists($path);
    }

    public function localFileUrl(string $path): string
    {
        return Storage::disk($this->getDisk())->url($path);
    }
}
