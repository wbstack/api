<?php

declare(strict_types=1);

namespace App\Enums;

enum WikiSuspensionActionReason: string {
    // For suspend
    case MANUAL_EXPIRY = 'manual_expiry';
    case AUTOMATIC_EXPIRY = 'automatic_expiry';
    case TOU_VIOLATION = 'tou_violation';
    case HP_VIOLATION = 'hp_violation';

    // For unsuspend
    case INDEFINITELY_ALLOWED = 'indefinitely_allowed';
}
