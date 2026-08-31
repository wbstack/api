<?php

// TODO: Move other enums into this namespace? E.g. WikiEntityImportStatus and MediawikiNamespace
// This is where Laravel puts them by default when using `php artisan make:enum`

namespace App\Enums;

enum ReviewSubmissionActionType: string {
    case SUBMITTED = 'submitted';
    case REVIEW_STARTED = 'review_started';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
}
