<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string {
    case WIKI_MANAGER = 'wiki_manager';
    case REVIEW_COMMITTEE_ADMIN = 'review_committee_admin';
}
