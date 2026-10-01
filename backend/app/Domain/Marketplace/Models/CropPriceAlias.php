<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;

class CropPriceAlias extends Model
{
    public const SOURCE_SEED = 'seed';

    public const SOURCE_AI = 'ai';

    public const SOURCE_ADMIN = 'admin';

    protected $fillable = [
        'alias_slug',
        'crop_slug',
        'source',
        'verified',
    ];

    protected $casts = [
        'verified' => 'boolean',
    ];
}
