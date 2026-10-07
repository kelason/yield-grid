<?php

declare(strict_types=1);

use App\Marketplace\Requests\CatalogFilterRequest;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

it('bounds catalog price and crop filters on both sides', function (): void {
    $factory = new Factory(new Translator(new ArrayLoader, 'en'));
    $request = new CatalogFilterRequest;
    foreach (['min_price', 'max_price', 'min_budget', 'max_budget'] as $field) {
        expect($factory->make([$field => 0], $request->rules())->passes())->toBeTrue()
            ->and($factory->make([$field => 9999999999.99], $request->rules())->passes())->toBeTrue()
            ->and($factory->make([$field => -0.01], $request->rules())->passes())->toBeFalse()
            ->and($factory->make([$field => 10000000000], $request->rules())->passes())->toBeFalse();
    }
    expect($factory->make(['crop' => str_repeat('a', 100)], $request->rules())->passes())->toBeTrue()
        ->and($factory->make(['crop' => str_repeat('a', 101)], $request->rules())->passes())->toBeFalse();
});

it('rejects inverted catalog ranges without querying the database', function (): void {
    $factory = new Factory(new Translator(new ArrayLoader, 'en'));
    $request = new CatalogFilterRequest;
    $validator = $factory->make(['min_price' => 100, 'max_price' => 99], $request->rules());
    $request->withValidator($validator);
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('max_price'))->toBeTrue();
});
