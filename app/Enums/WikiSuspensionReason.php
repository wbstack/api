<?php

declare(strict_types=1);

namespace App\Enums;

enum WikiSuspensionReason: string {
    case MANUAL_EXPIRY = 'manual_expiry';
    case AUTOMATIC_EXPIRY = 'automatic_expiry';
    case TOU_VIOLATION = 'tou_violation';
    case HP_VIOLATION = 'hp_violation';
}
