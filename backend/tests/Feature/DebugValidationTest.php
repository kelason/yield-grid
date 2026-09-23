<?php

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

it('tests required_if with boolean', function () {
    $v1 = Validator::make(['is_harvest_available' => false, 'estimated_harvest_date' => ''], [
        'is_harvest_available' => 'required|boolean',
        'estimated_harvest_date' => 'required_if:is_harvest_available,false|nullable|date',
    ]);

    $v2 = Validator::make(['is_harvest_available' => true, 'estimated_harvest_date' => ''], [
        'is_harvest_available' => 'required|boolean',
        'estimated_harvest_date' => 'required_if:is_harvest_available,false|nullable|date',
    ]);

    dump($v1->errors()->toArray(), $v2->errors()->toArray());
});
