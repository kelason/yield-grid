<?php

use App\Constants\FarmingConstants;
use Domain\Farming\Models\Farm;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->farmer = User::factory()->farmer()->create();
    // Farm without a city: skips the withValidator geocode guard (cf. PlotTest).
    $this->farm = Farm::create(['user_id' => $this->farmer->id, 'name' => 'Test Farm']);
    $this->url = "/api/v1/farms/{$this->farm->id}/plots";
    $this->triangle = [[0, 0], [0, 10], [10, 0], [0, 0]];
    $this->payload = [
        'name' => 'North Field',
        'soil_type' => 'loamy',
        'coordinates' => $this->triangle,
    ];
});

it('accepts a plot name at the max length', function () {
    $this->actingAs($this->farmer)->postJson($this->url, array_merge($this->payload, [
        'name' => str_repeat('a', FarmingConstants::PLOT_NAME_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a plot name one character over the max', function () {
    $this->actingAs($this->farmer)->postJson($this->url, array_merge($this->payload, [
        'name' => str_repeat('a', FarmingConstants::PLOT_NAME_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['name']);
});

it('accepts a triangle (minimum polygon points)', function () {
    $this->actingAs($this->farmer)->postJson($this->url, $this->payload)->assertCreated();
});

it('rejects fewer than the minimum polygon points', function () {
    $this->actingAs($this->farmer)->postJson($this->url, array_merge($this->payload, [
        'coordinates' => [[0, 0], [0, 10]],
    ]))->assertStatus(422)->assertJsonValidationErrors(['coordinates']);
});

it('rejects a coordinate pair that is not exactly two numbers', function () {
    $this->actingAs($this->farmer)->postJson($this->url, array_merge($this->payload, [
        'coordinates' => [[0, 0], [0, 10], [10, 0, 5]],
    ]))->assertStatus(422)->assertJsonValidationErrors(['coordinates.2']);
});

it('rejects an invalid soil type', function () {
    $this->actingAs($this->farmer)->postJson($this->url, array_merge($this->payload, [
        'soil_type' => 'moon-dust',
    ]))->assertStatus(422)->assertJsonValidationErrors(['soil_type']);
});
