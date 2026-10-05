<?php

use Tests\TestCase;

uses(TestCase::class);

it('adds CORS headers to API exception responses', function () {
    $this->getJson('/api/v1/route-that-does-not-exist')
        ->assertNotFound()
        ->assertHeader('Access-Control-Allow-Origin', '*');
});

it('does not add CORS headers to non-API exception responses', function () {
    $response = $this->get('/web-route-that-does-not-exist');

    $response->assertNotFound();
    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeFalse();
});
