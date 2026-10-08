<?php

declare(strict_types=1);

namespace App\Domain\Farming\Models;

use Illuminate\Database\Eloquent\Model;

final class FloodHazardZone extends Model
{
    protected $fillable = [
        'hazard_class',
        'return_period_years',
        'source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_period_years' => 'int',
        ];
    }
}
