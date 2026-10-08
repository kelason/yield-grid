<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Constants\AdminConstants;
use App\Constants\AuthConstants;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Repositories\AdminActionLogRepositoryInterface;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

final class CreateAdminAction
{
    private const string SUBJECT_TYPE = 'user';

    private const string CREATED_REASON = 'console provisioning';

    public function __construct(
        private readonly AdminActionLogRepositoryInterface $history,
    ) {}

    public function execute(string $name, string $email, string $password): User
    {
        $name = trim($name);
        $email = trim($email);

        $this->guardValidInput($name, $email, $password);
        $this->guardEmailAvailable($email);

        return DB::transaction(function () use ($name, $email, $password): User {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => UserRole::ADMIN->value,
            ]);

            $this->history->append(
                null,
                AdminAction::ADMIN_CREATED,
                self::SUBJECT_TYPE,
                (string) $user->id,
                self::CREATED_REASON,
                [],
                ['role' => UserRole::ADMIN->value],
            );

            return $user;
        });
    }

    private function guardValidInput(string $name, string $email, string $password): void
    {
        $this->guardName($name);
        $this->guardEmail($email);
        $this->guardPassword($password);
    }

    private function guardName(string $name): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('The name must not be blank.');
        }

        if (mb_strlen($name) > AuthConstants::NAME_MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('The name must not exceed %d characters.', AuthConstants::NAME_MAX_LENGTH)
            );
        }
    }

    private function guardEmail(string $email): void
    {
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('The email address is invalid.');
        }

        if (mb_strlen($email) > AuthConstants::EMAIL_MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('The email address must not exceed %d characters.', AuthConstants::EMAIL_MAX_LENGTH)
            );
        }
    }

    private function guardPassword(string $password): void
    {
        $length = mb_strlen($password);

        if ($length < AdminConstants::ADMIN_PASSWORD_MIN_LENGTH || $length > AuthConstants::PASSWORD_MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'The password must be between %d and %d characters.',
                    AdminConstants::ADMIN_PASSWORD_MIN_LENGTH,
                    AuthConstants::PASSWORD_MAX_LENGTH,
                )
            );
        }
    }

    private function guardEmailAvailable(string $email): void
    {
        if (User::where('email', $email)->exists()) {
            throw new InvalidArgumentException('The email address is already registered.');
        }
    }
}
