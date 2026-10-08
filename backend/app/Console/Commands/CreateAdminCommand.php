<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Users\Actions\CreateAdminAction;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Console\Command;
use InvalidArgumentException;

final class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create {email : The email address for the new admin account}';

    protected $description = 'Create a dedicated admin account';

    public function handle(CreateAdminAction $action): int
    {
        $email = trim((string) $this->argument('email'));

        $existing = User::where('email', $email)->first();

        if ($existing instanceof User) {
            return $this->reportExistingUser($existing);
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('The email address is invalid.');

            return self::FAILURE;
        }

        $credentials = $this->promptCredentials();

        if ($credentials === null) {
            return self::FAILURE;
        }

        return $this->createAdmin($action, $credentials['name'], $email, $credentials['password']);
    }

    private function createAdmin(CreateAdminAction $action, string $name, string $email, string $password): int
    {
        try {
            $user = $action->execute($name, $email, $password);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $user->sendEmailVerificationNotification();
        $this->info("Admin account created for {$user->email}.");

        return self::SUCCESS;
    }

    private function reportExistingUser(User $user): int
    {
        if ($user->role === UserRole::ADMIN) {
            $this->info("Admin already exists for {$user->email}; no changes made.");

            return self::SUCCESS;
        }

        $this->error("The email {$user->email} is already registered.");

        return self::FAILURE;
    }

    /**
     * @return array{name: string, password: string}|null
     */
    private function promptCredentials(): ?array
    {
        $name = (string) $this->ask('Name');
        $password = (string) $this->secret('Password');

        if ($password !== (string) $this->secret('Confirm password')) {
            $this->error('The passwords do not match.');

            return null;
        }

        return ['name' => $name, 'password' => $password];
    }
}
