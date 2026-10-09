<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Export once per process (paratest runs whole files per process). Path is
    // PID-scoped so parallel workers never share or delete each other's file.
    static $exported = false;

    if ($exported) {
        return;
    }

    $path = storage_path('testing/api-docs-'.getmypid().'/openapi.json');
    @mkdir(dirname($path), 0755, true);
    config(['docs.host' => 'api.yieldgrid.test']);
    Artisan::call('scramble:export', ['--path' => $path]);
    $exported = true;
});

afterAll(function () {
    $path = storage_path('testing/api-docs-'.getmypid().'/openapi.json');
    @unlink($path);
    @rmdir(dirname($path));
});

function apiDocsTestConfig(): void
{
    config([
        'docs.host' => 'api.yieldgrid.test',
        'docs.spec_path' => storage_path('testing/api-docs-'.getmypid().'/openapi.json'),
    ]);
}

it('serves the docs UI on the docs host', function () {
    apiDocsTestConfig();
    $this->get('http://api.yieldgrid.test/docs')
        ->assertOk()
        ->assertSee('apiDescriptionDocument', false)
        ->assertSee('openapi', false);
});

it('404s the docs UI on other hosts', function () {
    apiDocsTestConfig();
    $this->get('http://yieldgrid.test/docs')->assertNotFound();
});

it('serves a valid spec covering every route group', function () {
    apiDocsTestConfig();
    $response = $this->get('http://api.yieldgrid.test/docs/openapi.json')->assertOk();
    $spec = $response->json();
    expect($spec['openapi'])->toStartWith('3.');
    expect($spec['servers'][0]['url'])->toBe('https://api.yieldgrid.test');
    foreach (['/api/v1/register', '/api/v1/market/contracts', '/api/v1/market/demands', '/api/v1/market/prices/guide', '/api/v1/geo/regions', '/api/v1/webhooks/paymongo', '/api/v1/user'] as $path) {
        expect(array_keys($spec['paths']))->toContain($path);
    }
});

it('404s the spec on other hosts', function () {
    apiDocsTestConfig();
    $this->get('http://yieldgrid.test/docs/openapi.json')->assertNotFound();
});

it('throttles docs requests', function () {
    apiDocsTestConfig();
    Cache::flush();
    foreach (range(1, 60) as $i) {
        $this->get('http://api.yieldgrid.test/docs/openapi.json');
    }
    $this->get('http://api.yieldgrid.test/docs/openapi.json')->assertStatus(429);
});

it('returns 503 when the spec was never exported', function () {
    config(['docs.host' => 'api.yieldgrid.test', 'docs.spec_path' => '/nonexistent/openapi.json']);
    $this->get('http://api.yieldgrid.test/docs/openapi.json')
        ->assertStatus(503)->assertJson(['message' => 'API docs not generated yet.']);
});

it('does not expose Scramble default routes', function () {
    apiDocsTestConfig();
    $this->get('http://api.yieldgrid.test/docs/api')->assertNotFound();
    $this->get('http://api.yieldgrid.test/docs/api.json')->assertNotFound();
});
