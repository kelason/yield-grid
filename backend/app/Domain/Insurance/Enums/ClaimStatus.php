<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Enums;

enum ClaimStatus: string
{
    case DRAFT = 'draft';
    case NOTICE_OF_LOSS_FILED = 'notice_of_loss_filed';
    case FIELD_INSPECTION = 'field_inspection';
    case ADJUSTMENT = 'adjustment';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case REJECTED = 'rejected';
}
