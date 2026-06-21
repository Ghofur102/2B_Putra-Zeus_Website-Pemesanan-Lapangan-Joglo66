<?php

namespace App\Enums;

enum PaymentType: string
{
    case DOWN_PAYMENT = 'down payment';
    case FINAL_PAYMENT = 'final payment';
    case RESCHEDULE_FEE = 'reschedule fee';
    case REFUND = 'refund';
}
