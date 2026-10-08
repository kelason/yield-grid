<?php

use Illuminate\Support\Facades\App;
use Tests\TestCase;

uses(TestCase::class);

it('opens log viewer outside production', function () {
    $this->get('/log-viewer')->assertOk();
});

it('denies log viewer in production without web-server auth', function () {
    App::detectEnvironment(fn () => 'production');

    $this->get('/log-viewer')->assertForbidden();
});

it('opens log viewer in production with matching web-server user', function () {
    App::detectEnvironment(fn () => 'production');
    config()->set('log-viewer.basic_auth_user', 'deployer');

    $this->call('GET', '/log-viewer', [], [], [], ['REMOTE_USER' => 'deployer'])
        ->assertOk();
});
