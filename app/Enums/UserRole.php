<?php

namespace App\Enums;

enum UserRole: string {
    case WIKI_MANAGER = 'wiki_manager';
    case REVIEW_COMMITTEE_ADMIN = 'review_committee_admin';
}
