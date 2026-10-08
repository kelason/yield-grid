<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Repositories\AdminActionLogRepositoryInterface;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class ModerateMarketplaceContentAction
{
    public function __construct(
        private readonly AdminActionLogRepositoryInterface $history,
        private readonly ForwardContractRepositoryInterface $contracts,
    ) {}

    /**
     * Reversibly hide or restore a contract, listing, or demand. Records stay
     * in the database; hide only removes public visibility and blocks new
     * checkouts/offers. It never changes quantity, price, status, or payment
     * columns. Split-item hides normalize to the moderation root and return it.
     */
    public function execute(User $actor, ReportTargetType $type, string $id, bool $hide, string $reason): Model
    {
        $reason = trim($reason);

        $this->guardTargetType($type);
        $this->guardReason($reason);

        return DB::transaction(fn (): Model => match ($type) {
            ReportTargetType::CONTRACT => $this->moderateContract($actor, (int) $id, $hide, $reason),
            ReportTargetType::LISTING => $this->moderateListing($actor, (int) $id, $hide, $reason),
            ReportTargetType::DEMAND => $this->moderateDemand($actor, (int) $id, $hide, $reason),
            default => throw new InvalidArgumentException('Only contracts, listings, and demands can be moderated here.'),
        });
    }

    private function moderateContract(User $actor, int $id, bool $hide, string $reason): ForwardContract
    {
        $root = $this->contracts->findModerationRootLocked($id);

        if ($root->id !== $id) {
            $this->contracts->findByIdLocked($id);
        }

        $this->transition($actor, ReportTargetType::CONTRACT, $root, $hide, $reason);

        return $root->refresh();
    }

    private function moderateListing(User $actor, int $id, bool $hide, string $reason): HarvestListing
    {
        $root = HarvestListing::findModerationRootLocked($id);

        if ($root->id !== $id) {
            HarvestListing::whereKey($id)->lockForUpdate()->firstOrFail();
        }

        $this->transition($actor, ReportTargetType::LISTING, $root, $hide, $reason);

        return $root->refresh();
    }

    private function moderateDemand(User $actor, int $id, bool $hide, string $reason): CropDemand
    {
        $demand = CropDemand::whereKey($id)->lockForUpdate()->first();

        if (! $demand instanceof CropDemand) {
            throw (new ModelNotFoundException)->setModel(CropDemand::class, $id);
        }

        $this->transition($actor, ReportTargetType::DEMAND, $demand, $hide, $reason);

        return $demand->refresh();
    }

    private function transition(User $actor, ReportTargetType $type, Model $model, bool $hide, string $reason): void
    {
        if (($model->getAttribute('hidden_at') !== null) === $hide) {
            throw new LogicException($this->repeatedMessage($type, $hide));
        }

        $before = $this->moderationState($model);

        $model->forceFill($hide
            ? ['hidden_at' => now(), 'hidden_by' => $actor->id, 'hidden_reason' => $reason]
            : ['hidden_at' => null, 'hidden_by' => null, 'hidden_reason' => null]);
        $model->save();

        $this->history->append(
            $actor->id,
            $hide ? AdminAction::CONTENT_HIDDEN : AdminAction::CONTENT_RESTORED,
            $type->value,
            (string) $model->getKey(),
            $reason,
            $before,
            $this->moderationState($model),
        );
    }

    private function guardTargetType(ReportTargetType $type): void
    {
        if ($type !== ReportTargetType::CONTRACT && $type !== ReportTargetType::LISTING && $type !== ReportTargetType::DEMAND) {
            throw new InvalidArgumentException('Only contracts, listings, and demands can be moderated here.');
        }
    }

    private function guardReason(string $reason): void
    {
        if ($reason === '' || mb_strlen($reason) > MarketplaceConstants::MODERATION_REASON_MAX_LENGTH) {
            throw new InvalidArgumentException('The reason must be between 1 and 500 characters.');
        }
    }

    private function repeatedMessage(ReportTargetType $type, bool $hide): string
    {
        $label = match ($type) {
            ReportTargetType::CONTRACT => 'Contract',
            ReportTargetType::LISTING => 'Listing',
            default => 'Demand',
        };

        return $hide ? "{$label} is already hidden." : "{$label} is not hidden.";
    }

    /**
     * @return array{hidden_at: ?string, hidden_by: ?int, hidden_reason: ?string}
     */
    private function moderationState(Model $model): array
    {
        $hiddenAt = $model->getAttribute('hidden_at');

        return [
            'hidden_at' => $hiddenAt instanceof Carbon ? $hiddenAt->toISOString() : null,
            'hidden_by' => $model->getAttribute('hidden_by'),
            'hidden_reason' => $model->getAttribute('hidden_reason'),
        ];
    }
}
