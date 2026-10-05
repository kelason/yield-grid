<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Models;

use App\Domain\Insurance\Enums\InsuranceProgram;
use App\Domain\Insurance\Enums\Season;
use Illuminate\Database\Eloquent\Model;

final class PlantingWindow extends Model
{
    protected $fillable = [
        'region_code',
        'program',
        'season',
        'window_start_month',
        'window_start_day',
        'window_end_month',
        'window_end_day',
        'source',
    ];

    protected $casts = [
        'program' => InsuranceProgram::class,
        'season' => Season::class,
        'window_start_month' => 'integer',
        'window_start_day' => 'integer',
        'window_end_month' => 'integer',
        'window_end_day' => 'integer',
    ];
}
