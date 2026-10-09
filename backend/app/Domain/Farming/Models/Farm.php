<?php

declare(strict_types=1);

namespace Domain\Farming\Models;

use Database\Factories\FarmFactory;
use Domain\Farming\Enums\VerificationMethod;
use Domain\Farming\Enums\VerificationStatus;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'city',
        'state',
        'country',
        'zip',
        'total_area',
        'verification_status',
        'verification_method',
        'verification_note',
        'verified_by',
        'verified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verification_status' => VerificationStatus::class,
            'verification_method' => VerificationMethod::class,
            'verified_at' => 'datetime',
        ];
    }

    protected static function newFactory()
    {
        return FarmFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->verification_status ??= VerificationStatus::PENDING;
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Plot, $this>
     */
    public function plots(): HasMany
    {
        return $this->hasMany(Plot::class);
    }
}
