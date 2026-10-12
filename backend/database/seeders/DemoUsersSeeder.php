<?php

declare(strict_types=1);

namespace Database\Seeders;

use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    private const PASSWORD = 'Testing123456!';

    /**
     * Demo accounts seeded with a shared known password.
     *
     * @var array<string, array{name: string, role: UserRole}>
     */
    private const USERS = [
        'farmer@example.com' => ['name' => 'Demo Farmer', 'role' => UserRole::FARMER],
        'buyer@example.com' => ['name' => 'Demo Buyer', 'role' => UserRole::BUYER],
        'yieldgridph@example.com' => ['name' => 'YieldGrid Admin', 'role' => UserRole::ADMIN],
    ];

    public function run(): void
    {
        foreach (self::USERS as $email => $attributes) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $attributes['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'role' => $attributes['role'],
                ]
            );

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }
    }
}
