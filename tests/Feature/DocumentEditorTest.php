<?php

use App\Models\User;
use App\Support\OnlyOfficeJwt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    config()->set('onlyoffice.jwt_secret', 'onlyoffice-test-secret');
    config()->set('onlyoffice.storage_disk', 'public');
    config()->set('onlyoffice.doc_server_url', 'http://localhost:8081');
    config()->set('onlyoffice.verify_ssl', true);
    Storage::fake('public');
});

test('authenticated user can open the onlyoffice editor page for a docx file', function () {
    Storage::disk('public')->put('contracts/sample.docx', 'original docx');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('documents.edit', [
        'path' => 'contracts/sample.docx',
    ]));

    $response
        ->assertOk()
        ->assertSee('DocsAPI.DocEditor', false)
        ->assertSee('contracts/sample.docx');
});

test('documents file path is accessible only with valid jwt token', function () {
    Storage::disk('public')->put('contracts/sample.docx', 'original docx');

    $token = OnlyOfficeJwt::encode(['path' => 'contracts/sample.docx'], config('onlyoffice.jwt_secret'));
    $fileUrl = route('documents.file', [
        'path' => 'contracts/sample.docx',
        'token' => $token,
    ]);

    $this->get($fileUrl)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $this->get(route('documents.file', ['path' => 'contracts/sample.docx']))
        ->assertStatus(401);
});

test('onlyoffice callback persists the updated docx back to the public disk', function () {
    Storage::disk('public')->put('contracts/sample.docx', 'old-content');

    Http::fake([
        'https://document-server.test/cache/sample.docx' => Http::response('new-content', 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]),
    ]);

    $payload = [
        'status' => 2,
        'url' => 'https://document-server.test/cache/sample.docx',
        'key' => 'sample-key',
        'users' => ['1'],
    ];

    $token = OnlyOfficeJwt::encode(['document' => 'callback-validation'], config('onlyoffice.jwt_secret'));

    $url = URL::temporarySignedRoute('documents.callback', now()->addMinutes(30), [
        'path' => 'contracts/sample.docx',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson($url, $payload);

    $response->assertOk()->assertExactJson(['error' => 0, 'path' => 'contracts/sample.docx']);
    Storage::disk('public')->assertExists('contracts/sample.docx');
    expect(Storage::disk('public')->get('contracts/sample.docx'))->toBe('new-content');
});

test('onlyoffice callback with save_as true stores file as a new path', function () {
    Storage::disk('public')->put('contracts/sample.docx', 'old-content');

    Http::fake([
        'https://document-server.test/cache/sample.docx' => Http::response('new-content-as-new', 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]),
    ]);

    $payload = [
        'status' => 2,
        'url' => 'https://document-server.test/cache/sample.docx',
        'key' => 'sample-key',
        'users' => ['1'],
    ];

    $token = OnlyOfficeJwt::encode(['document' => 'callback-validation'], config('onlyoffice.jwt_secret'));

    $url = URL::temporarySignedRoute('documents.callback', now()->addMinutes(30), [
        'path' => 'contracts/sample.docx',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson($url.'?save_as=1', $payload);

    $response->assertOk();

    $body = $response->json();

    expect($body['error'])->toBe(0);
    expect($body['path'])->not->toBe('contracts/sample.docx');

    Storage::disk('public')->assertExists($body['path']);
    expect(Storage::disk('public')->get($body['path']))->toBe('new-content-as-new');

    Storage::disk('public')->assertExists('contracts/sample.docx');
    expect(Storage::disk('public')->get('contracts/sample.docx'))->toBe('old-content');
});
