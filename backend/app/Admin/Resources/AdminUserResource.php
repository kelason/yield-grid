<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'email_verified_at' => $this->email_verified_at,
            'suspended_at' => $this->suspended_at,
            'suspended_reason' => $this->suspended_reason,
            'created_at' => $this->created_at,
        ];
    }
}
