<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Enums;

enum EnrollmentStatus: string
{
    case DRAFT = 'draft';
    case DOCUMENTS_READY = 'documents_ready';
    case SUBMITTED_TO_MAO = 'submitted_to_mao';
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
}
