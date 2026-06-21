<?php

namespace App\Enums;

enum CancelRefundStatus: string
{
    case NONE = 'None';
    case FULL = 'Full';
    case PARTIAL = 'Partial';
}
