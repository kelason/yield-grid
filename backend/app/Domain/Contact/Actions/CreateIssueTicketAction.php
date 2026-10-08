<?php

declare(strict_types=1);

namespace App\Domain\Contact\Actions;

use App\Constants\IssueConstants;
use App\Domain\Contact\Enums\IssueCategory;
use App\Domain\Contact\Enums\IssueStatus;
use App\Domain\Contact\Models\IssueTicket;
use App\Domain\Contact\Repositories\IssueTicketRepositoryInterface;
use Domain\Users\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

final class CreateIssueTicketAction
{
    private const string UNIQUE_VIOLATION_SQLSTATE = '23505';

    public function __construct(
        private readonly IssueTicketRepositoryInterface $issues,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(User $reporter, array $validated): IssueTicket
    {
        $attributes = $this->normalize($validated);

        $existing = $this->issues->findByRequestId($reporter->id, $attributes['client_request_id']);

        if ($existing instanceof IssueTicket) {
            $this->guardReplay($existing, $attributes);

            return $existing;
        }

        return $this->insertTicket($reporter, $attributes);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{category: string, subject: string, description: string, page_path: ?string, client_request_id: string}
     */
    private function normalize(array $validated): array
    {
        $category = $validated['category'] ?? null;
        $category = $category instanceof IssueCategory ? $category->value : (string) $category;

        if (IssueCategory::tryFrom($category) === null) {
            throw new InvalidArgumentException('The selected category is invalid.');
        }

        $subject = $this->boundedText($validated, 'subject', IssueConstants::SUBJECT_MAX_LENGTH);
        $description = $this->boundedText($validated, 'description', IssueConstants::DESCRIPTION_MAX_LENGTH);

        $requestId = (string) ($validated['client_request_id'] ?? '');

        if (! Str::isUuid($requestId)) {
            throw new InvalidArgumentException('The client request id must be a valid UUID.');
        }

        return [
            'category' => $category,
            'subject' => $subject,
            'description' => $description,
            'page_path' => $this->normalizePagePath($validated['page_path'] ?? null),
            'client_request_id' => $requestId,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function boundedText(array $validated, string $field, int $max): string
    {
        $text = trim((string) ($validated[$field] ?? ''));

        if ($text === '' || mb_strlen($text) > $max) {
            throw new InvalidArgumentException("The {$field} must be between 1 and {$max} characters.");
        }

        return $text;
    }

    private function normalizePagePath(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $path = trim((string) $value);

        if ($path === '') {
            return null;
        }

        if (mb_strlen($path) > IssueConstants::PAGE_PATH_MAX_LENGTH || ! $this->isSafeRelativePath($path)) {
            throw new InvalidArgumentException('The page path must be a relative path like /dashboard/issues.');
        }

        return $path;
    }

    private function isSafeRelativePath(string $path): bool
    {
        if (! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return false;
        }

        return preg_match('~[?#\s\\\\\x00-\x1F\x7F]|//~', $path) !== 1;
    }

    /**
     * @param  array{category: string, subject: string, description: string, page_path: ?string, client_request_id: string}  $attributes
     */
    private function insertTicket(User $reporter, array $attributes): IssueTicket
    {
        try {
            return DB::transaction(fn (): IssueTicket => $this->issues->create([
                'user_id' => $reporter->id,
                'category' => $attributes['category'],
                'subject' => $attributes['subject'],
                'description' => $attributes['description'],
                'page_path' => $attributes['page_path'],
                'status' => IssueStatus::OPEN->value,
                'client_request_id' => $attributes['client_request_id'],
            ]));
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            return $this->recoverDuplicate($reporter->id, $attributes, $e);
        }
    }

    /**
     * @param  array{category: string, subject: string, description: string, page_path: ?string, client_request_id: string}  $attributes
     */
    private function recoverDuplicate(int $reporterId, array $attributes, QueryException $conflict): IssueTicket
    {
        $recovered = $this->issues->findByRequestId($reporterId, $attributes['client_request_id']);

        if (! $recovered instanceof IssueTicket) {
            throw $conflict;
        }

        $this->guardReplay($recovered, $attributes);

        return $recovered;
    }

    /**
     * @param  array{category: string, subject: string, description: string, page_path: ?string, client_request_id: string}  $attributes
     */
    private function guardReplay(IssueTicket $existing, array $attributes): void
    {
        $same = $existing->getRawOriginal('category') === $attributes['category']
            && $existing->subject === $attributes['subject']
            && $existing->description === $attributes['description']
            && $existing->page_path === $attributes['page_path'];

        if (! $same) {
            throw new LogicException('This request key was already used with a different ticket.');
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return (string) $e->getCode() === self::UNIQUE_VIOLATION_SQLSTATE;
    }
}
