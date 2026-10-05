<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Enums;

enum ReminderType: string
{
    case ENROLLMENT_WINDOW = 'enrollment_window';
    case NOTICE_OF_LOSS_DEADLINE = 'notice_of_loss_deadline';
    case CLAIM_FOLLOWUP = 'claim_followup';
    case RENEWAL = 'renewal';
}
