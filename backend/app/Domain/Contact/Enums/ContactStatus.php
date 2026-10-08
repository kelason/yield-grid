<?php

declare(strict_types=1);

namespace App\Domain\Contact\Enums;

enum ContactStatus: string
{
    case UNREAD = 'unread';
    case READ = 'read';
    case REPLIED = 'replied';
    case CLOSED = 'closed';
}
