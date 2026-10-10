<?php

declare(strict_types=1);

use App\Shared\Controllers\ApiDocsController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

it('documents bearer auth for protected routes', function () {
    apiDocsTestConfig();
    $spec = $this->get('http://api.yieldgrid.test/docs/openapi.json')->assertOk()->json();
    expect($spec['components']['securitySchemes']['http']['scheme'])->toBe('bearer');
    expect($spec['security'][0])->toHaveKey('http');
    expect($spec['paths']['/api/v1/register']['post']['security'])->toBe([]);
    expect($spec['paths']['/api/v1/user']['get'])->not->toHaveKey('security');
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

it('returns 503 JSON from the UI when the spec was never exported', function () {
    config(['docs.host' => 'api.yieldgrid.test', 'docs.spec_path' => '/nonexistent/openapi.json']);
    $this->get('http://api.yieldgrid.test/docs')
        ->assertStatus(503)->assertJson(['message' => 'API docs not generated yet.']);
});

it('redirects trailing-slash docs to canonical', function () {
    // In-process HTTP calls trim the slash before the app sees it, so invoke
    // the controller directly: Request::create preserves /docs/ in getPathInfo.
    config(['docs.host' => 'api.yieldgrid.test']);
    $response = (new ApiDocsController)->ui(Request::create('http://api.yieldgrid.test/docs/', 'GET'));
    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getStatusCode())->toBe(301);
    expect(parse_url($response->getTargetUrl(), PHP_URL_PATH))->toBe('/docs');
});

it('does not expose Scramble default routes', function () {
    apiDocsTestConfig();
    $this->get('http://api.yieldgrid.test/docs/api')->assertNotFound();
    $this->get('http://api.yieldgrid.test/docs/api.json')->assertNotFound();
});

it('exports a valid spec via artisan', function () {
    $path = storage_path('testing/openapi-export-check.json');
    @mkdir(dirname($path), 0755, true);
    $this->artisan('scramble:export', ['--path' => $path])->assertSuccessful();
    $spec = json_decode((string) file_get_contents($path), true);
    expect($spec['openapi'])->toStartWith('3.');
    expect(array_keys($spec['paths']))->toContain('/api/v1/market/prices/guide');
    unlink($path);
});
