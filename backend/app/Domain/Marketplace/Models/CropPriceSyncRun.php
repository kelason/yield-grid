<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;

class CropPriceSyncRun extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'status',
        'rows_upserted',
        'error',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'rows_upserted' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
