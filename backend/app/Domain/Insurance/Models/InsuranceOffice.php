<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Models;

use Illuminate\Database\Eloquent\Model;

final class InsuranceOffice extends Model
{
    protected $fillable = [
        'name',
        'region_code',
        'city',
        'address',
        'phone',
        'source_note',
    ];
}
