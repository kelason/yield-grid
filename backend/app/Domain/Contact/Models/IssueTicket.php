<?php

declare(strict_types=1);

namespace App\Domain\Contact\Models;

use App\Domain\Contact\Enums\IssueCategory;
use App\Domain\Contact\Enums\IssueStatus;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueTicket extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'subject',
        'description',
        'page_path',
        'status',
        'resolution',
        'resolved_by',
        'resolved_at',
        'version',
        'client_request_id',
    ];

    protected $casts = [
        'category' => IssueCategory::class,
        'status' => IssueStatus::class,
        'resolved_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
