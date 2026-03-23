<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ basename($documentPath) }} | DOCX Editor</title>
    <script src="{{ $docServerUrl }}/web-apps/apps/api/documents/api.js"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            background: #f3f5f7;
            color: #111827;
            font-family: Arial, sans-serif;
        }

        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .toolbar h1 {
            margin: 0;
            font-size: 1rem;
        }

        .toolbar p {
            margin: 0.25rem 0 0;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .toolbar a {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: #111827;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.65rem 1rem;
            background: #ffffff;
        }

        .editor-shell {
            height: calc(100% - 77px);
            position: relative;
        }

        #onlyoffice-editor {
            height: 100%;
        }

        #editor-status {
            position: absolute;
            inset: 0;
            display: none;
            place-items: center;
            padding: 2rem;
            background: #f9fafb;
        }

        #editor-status.is-visible {
            display: grid;
        }

        .status-card {
            max-width: 42rem;
            padding: 1.25rem 1.5rem;
            border: 1px solid #fecaca;
            border-radius: 0.75rem;
            background: #fff1f2;
            color: #991b1b;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06);
        }

        .status-card h2 {
            margin: 0 0 0.5rem;
            font-size: 1rem;
        }

        .status-card p {
            margin: 0.5rem 0;
            line-height: 1.5;
        }

        .status-card code {
            background: rgba(255, 255, 255, 0.7);
            padding: 0.1rem 0.35rem;
            border-radius: 0.3rem;
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>DOCX Editor</h1>
            <p>{{ $documentPath }}</p>
        </div>
        <a href="{{ $downloadUrl }}">Download current file</a>
    </div>

    <div class="editor-shell">
        <div id="onlyoffice-editor"></div>
        <div id="editor-status" aria-live="polite">
            <div class="status-card">
                <h2>ONLYOFFICE editor could not start</h2>
                <p id="editor-status-message"></p>
            </div>
        </div>
    </div>

    <script>
        const onlyOfficeConfig = @json($editorConfig);
        onlyOfficeConfig.token = @json($editorToken);
        const docServerUrl = @json($docServerUrl);
        const statusBox = document.getElementById('editor-status');
        const statusMessage = document.getElementById('editor-status-message');

        function showEditorError(message) {
            statusMessage.innerHTML = message;
            statusBox.classList.add('is-visible');
        }

        function bootOnlyOffice() {
            if (!window.DocsAPI || typeof window.DocsAPI.DocEditor !== 'function') {
                showEditorError(
                    'The ONLYOFFICE client library was not found at <code>' + docServerUrl + '</code>. ' +
                    'Update <code>ONLYOFFICE_DOC_SERVER_URL</code> to your real ONLYOFFICE Document Server.'
                );
                return;
            }

            statusBox.classList.remove('is-visible');
            new window.DocsAPI.DocEditor('onlyoffice-editor', onlyOfficeConfig);
        }

        // Check if API is already loaded, otherwise wait for it
        if (window.DocsAPI) {
            bootOnlyOffice();
        } else {
            window.addEventListener('load', function() {
                setTimeout(bootOnlyOffice, 100); // Small delay to ensure script is fully loaded
            });
        }
    </script>
</body>
</html>