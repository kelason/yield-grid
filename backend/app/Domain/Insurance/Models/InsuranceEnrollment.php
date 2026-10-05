<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Models;

use App\Domain\Insurance\Enums\EnrollmentStatus;
use App\Domain\Insurance\Enums\InsuranceProgram;
use App\Domain\Insurance\Enums\PackStatus;
use App\Domain\Insurance\Enums\Season;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InsuranceEnrollment extends Model
{
    protected $attributes = [
        'status' => 'draft',
        'pack_status' => 'none',
    ];

    protected $fillable = [
        'user_id',
        'plot_id',
        'program',
        'season',
        'season_year',
        'status',
        'cic_number',
        'coverage_amount_php',
        'enrolled_at',
        'expires_at',
        'notes',
        'pack_status',
        'pack_path',
        'pack_token',
        'pack_generated_at',
        'pack_expires_at',
    ];

    protected $casts = [
        'program' => InsuranceProgram::class,
        'season' => Season::class,
        'season_year' => 'integer',
        'status' => EnrollmentStatus::class,
        'coverage_amount_php' => 'decimal:2',
        'enrolled_at' => 'datetime',
        'expires_at' => 'datetime',
        'pack_status' => PackStatus::class,
        'pack_generated_at' => 'datetime',
        'pack_expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Plot, $this>
     */
    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class);
    }

    /**
     * @return HasMany<InsuranceClaim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class, 'enrollment_id');
    }
}
