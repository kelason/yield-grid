<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum AdminAction: string
{
    case ADMIN_CREATED = 'admin_created';
    case USER_SUSPENDED = 'user_suspended';
    case USER_UNSUSPENDED = 'user_unsuspended';
    case CONTACT_READ = 'contact_read';
    case CONTACT_CLOSED = 'contact_closed';
    case CONTACT_REOPENED = 'contact_reopened';
    case REPLY_QUEUED = 'reply_queued';
    case REPLY_RETRIED = 'reply_retried';
    case REPORT_DECIDED = 'report_decided';
    case CONTENT_HIDDEN = 'content_hidden';
    case CONTENT_RESTORED = 'content_restored';
    case ISSUE_TRANSITIONED = 'issue_transitioned';
}
