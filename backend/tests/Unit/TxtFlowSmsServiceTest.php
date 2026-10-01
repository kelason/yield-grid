<?php

use App\Domain\Marketplace\Services\SmsServiceInterface;
use App\Infrastructure\Marketplace\Services\TxtFlowSmsService;
use Tests\TestCase;

uses(TestCase::class);

it('is disabled by default', function () {
    config()->set('services.txtflow.enabled', false);

    $service = app(SmsServiceInterface::class);

    expect($service)->toBeInstanceOf(TxtFlowSmsService::class)
        ->and($service->isConfigured())->toBeFalse();

    $result = $service->send('+639171234567', 'Price alert: rice is now ₱50/kg.');

    expect($result->sent)->toBeFalse()
        ->and($result->provider)->toBe('txtflow');
});
