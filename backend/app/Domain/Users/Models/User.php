<?php

declare(strict_types=1);

namespace Domain\Users\Models;

use App\Domain\Chat\Models\ChatParticipant;
use App\Domain\Community\Models\ForumThread;
use Database\Factories\UserFactory;
use Domain\Farming\Models\Farm;
use Domain\Users\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property Carbon|null $suspended_at
 * @property string|null $suspended_reason
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar_url',
        'phone',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'suspended_at' => 'datetime',
        ];
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    /**
     * @return HasMany<Farm, $this>
     */
    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }

    /**
     * @return HasMany<ForumThread, $this>
     */
    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class, 'user_id');
    }

    /**
     * @return HasMany<ChatParticipant, $this>
     */
    public function chatParticipations(): HasMany
    {
        return $this->hasMany(ChatParticipant::class, 'user_id');
    }

    /**
     * @return HasMany<UserAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }

    public function defaultAddress(): ?UserAddress
    {
        /** @var UserAddress|null */
        return $this->addresses()->where('is_default', true)->first()
            ?? $this->addresses()->oldest()->first();
    }

    public function hasMarketplaceAddress(): bool
    {
        return $this->addresses()->exists();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }
}
