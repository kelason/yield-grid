<?php

declare(strict_types=1);

use App\Community\Requests\ForumFilterRequest;
use App\Constants\ForumConstants;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

it('bounds optional forum search without changing an empty search', function (): void {
    $factory = new Factory(new Translator(new ArrayLoader, 'en'));
    $request = new ForumFilterRequest;
    expect($factory->make(['search' => ''], $request->rules())->passes())->toBeTrue()
        ->and($factory->make(['search' => str_repeat('a', ForumConstants::SEARCH_MAX_LENGTH)], $request->rules())->passes())->toBeTrue()
        ->and($factory->make(['search' => str_repeat('a', ForumConstants::SEARCH_MAX_LENGTH + 1)], $request->rules())->passes())->toBeFalse()
        ->and($factory->make(['search' => ['invalid']], $request->rules())->passes())->toBeFalse();
});
