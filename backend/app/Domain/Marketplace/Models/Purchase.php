<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Models;

use App\Domain\Marketplace\Enums\PaymentMethod;
use App\Domain\Marketplace\Enums\PaymentStatus;
use Database\Factories\PurchaseFactory;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return PurchaseFactory::new();
    }

    protected $fillable = [
        'buyer_id',
        'forward_contract_id',
        'paymongo_payment_id',
        'paymongo_checkout_id',
        'payment_method',
        'amount_paid',
        'currency',
        'payment_status',
        'purchased_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'payment_method' => PaymentMethod::class,
        'payment_status' => PaymentStatus::class,
        'purchased_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * @return BelongsTo<ForwardContract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(ForwardContract::class, 'forward_contract_id');
    }
}
