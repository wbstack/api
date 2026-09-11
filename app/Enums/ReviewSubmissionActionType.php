<?php

declare(strict_types=1);

namespace App\Enums;

enum ReviewSubmissionActionType: string {
    case SUBMITTED = 'submitted';
    case REVIEW_STARTED = 'review_started';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
}
