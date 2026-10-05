<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Models;

use App\Domain\Insurance\Enums\ClaimStatus;
use App\Domain\Insurance\Enums\LossCause;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InsuranceClaim extends Model
{
    protected $attributes = [
        'status' => 'draft',
    ];

    protected $fillable = [
        'enrollment_id',
        'loss_date',
        'cause',
        'description',
        'status',
        'notice_of_loss_filed_at',
        'paid_amount_php',
        'paid_at',
    ];

    protected $casts = [
        'loss_date' => 'date',
        'cause' => LossCause::class,
        'status' => ClaimStatus::class,
        'notice_of_loss_filed_at' => 'datetime',
        'paid_amount_php' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<InsuranceEnrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(InsuranceEnrollment::class, 'enrollment_id');
    }
}
