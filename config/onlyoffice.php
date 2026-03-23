<?php

return [
    'doc_server_url' => rtrim((string) env('ONLYOFFICE_DOC_SERVER_URL', 'http://localhost:8081'), '/'),
    'jwt_secret' => env('ONLYOFFICE_JWT_SECRET'),
    'storage_disk' => env('ONLYOFFICE_STORAGE_DISK', 'public'),
    'document_url_ttl_minutes' => (int) env('ONLYOFFICE_DOCUMENT_URL_TTL', 60),
    'callback_url_ttl_minutes' => (int) env('ONLYOFFICE_CALLBACK_URL_TTL', 1440),
    'verify_ssl' => filter_var(env('ONLYOFFICE_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
];
