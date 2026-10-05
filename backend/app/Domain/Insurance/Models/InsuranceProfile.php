<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Models;

use App\Domain\Insurance\Enums\RsbsaStatus;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InsuranceProfile extends Model
{
    protected $attributes = [
        'rsbsa_status' => 'not_registered',
    ];

    protected $fillable = [
        'user_id',
        'rsbsa_number',
        'rsbsa_status',
    ];

    protected $casts = [
        'rsbsa_status' => RsbsaStatus::class,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
