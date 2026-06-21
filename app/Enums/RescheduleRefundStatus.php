<?php

namespace App\Enums;

enum RescheduleRefundStatus: string
{
    case NONE = 'none';
    case DEPOSIT_REQUIRED = 'deposit required';
    case REFUND_REQUIRED = 'refund required';
}
