<?php

use App\Constants\ForumConstants;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
});

it('accepts a small image attachment', function () {
    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->image('plot.jpg', 100, 100),
        'attachable_type' => 'thread',
    ])->assertCreated();
});

it('rejects a non-image attachment', function () {
    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        'attachable_type' => 'thread',
    ])->assertStatus(422)->assertJsonValidationErrors(['file']);
});

it('rejects an attachment over the max size', function () {
    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->create(
            'huge.png',
            ForumConstants::ATTACHMENT_MAX_SIZE_KB + 1,
            'image/png'
        ),
        'attachable_type' => 'thread',
    ])->assertStatus(422)->assertJsonValidationErrors(['file']);
});

it('rejects an unknown attachable type', function () {
    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->image('plot.jpg', 100, 100),
        'attachable_type' => 'user',
    ])->assertStatus(422)->assertJsonValidationErrors(['attachable_type']);
});
