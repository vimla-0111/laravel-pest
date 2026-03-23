<?php

namespace App\Http\Controllers;

use App\Services\OnlyOfficeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentEditorController extends Controller
{
    public function __construct(protected OnlyOfficeService $onlyOfficeService) {}

    public function edit(Request $request, string $path): View
    {
        $path = $this->onlyOfficeService->normalizePath($path);

        abort_unless($this->onlyOfficeService->isDocx($path), 422, 'Only DOCX files are supported.');
        abort_unless($this->onlyOfficeService->localFileExists($path), 404, 'Document not found.');

        $this->onlyOfficeService->ensureConfigured();

        $editorConfig = $this->onlyOfficeService->editorConfig($request, $path);
        // dd($editorConfig);
        return view('documents.edit', [
            'documentPath' => $path,
            'downloadUrl' => $this->onlyOfficeService->localFileUrl($path),
            'editorConfig' => $editorConfig,
            'editorToken' => $this->onlyOfficeService->editorToken($editorConfig),
            'docServerUrl' => rtrim((string) config('onlyoffice.doc_server_url'), '/'),
        ]);
    }

    public function file(Request $request, string $path)
    {
        $path = $this->onlyOfficeService->normalizePath($path);

        abort_unless($this->onlyOfficeService->isDocx($path), 422, 'Only DOCX files are supported.');
        abort_unless($this->onlyOfficeService->localFileExists($path), 404, 'Document not found.');

        // return Storage::disk($this->onlyOfficeService->getDisk())->path($path);
        return Storage::disk($this->onlyOfficeService->getDisk())->response($path, basename($path), [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function callback(Request $request, string $path): JsonResponse
    {
        $path = $this->onlyOfficeService->normalizePath($path);

        $payload = $request->json()->all();

        if (isset($payload['payload']) && is_array($payload['payload'])) {
            $payload = $payload['payload'];
        }

        abort_unless($this->onlyOfficeService->isDocx($path), 422, 'Only DOCX files are supported.');

        $saveAsNew = $request->boolean('save_as', false);

        $savedPath = $this->onlyOfficeService->persistCallbackDocument($payload, $path, $saveAsNew);

        return response()->json(['error' => 0, 'path' => $savedPath]);
    }
}
