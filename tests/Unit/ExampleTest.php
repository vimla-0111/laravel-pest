<?php

use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('that true is true', function () {
    expect(true)->toBeTrue();
});

it('checks that post belongs to user', function () {
    $user = fakeUser();
    $post = Post::factory()->createdBy($user->id)->create();

    $this->assertEquals($post->created_by,$user->id);
    $this->assertDatabaseCount('posts',1);
    $this->assertDatabaseCount('users',1);
    $this->assertInstanceOf(User::class, $post->creator);
});

// it('verify validation', function () {
    
// });


it('validates required fields correctly', function (array $data, array $expectedErrors) {
    // 1. Get the validation rules from the request
    $rules = (new StorePostRequest())->rules();

    // 2. Create the validator instance
    $validator = Validator::make($data, $rules);

    // 3. Assert the validation state
    $this->assertFalse($validator->passes(), 'Validation should fail for invalid data.');
    $this->assertEqualsCanonicalizing($expectedErrors, $validator->errors()->keys(), 'Validation errors should match the expected fields.');

})->with([
    'missing_title' => [
        ['content' => 'Content'], 
        ['title'] // Expect error on 'title'
    ],
    'title_too_short' => [
        ['title' => 'ab', 'content' => 'Content'], 
        ['title'] // Expect error on 'title'
    ],
    'missing_body' => [
        ['title' => 'Title'], 
        ['content'] // Expect error on 'body'
    ],
    // Add more failure scenarios...
]);