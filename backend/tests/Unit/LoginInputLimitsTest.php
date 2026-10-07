<?php

declare(strict_types=1);

use App\Auth\Requests\LoginRequest;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

it('bounds login credentials without rejecting the supported maximum', function (): void {
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));
    $rules = (new LoginRequest)->rules();
    expect($validator->make(['email' => 'farmer@example.com', 'password' => str_repeat('x', 255)], $rules)->passes())->toBeTrue()
        ->and($validator->make(['email' => 'farmer@example.com', 'password' => str_repeat('x', 256)], $rules)->passes())->toBeFalse()
        ->and($validator->make(['email' => 'farmer@example.com', 'password' => []], $rules)->passes())->toBeFalse();
});
