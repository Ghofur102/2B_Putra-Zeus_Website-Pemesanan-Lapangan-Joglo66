<?php

namespace App\Enums;

enum PaymentTypeEnum: string
{
    case DownPayment = 'down payment';
    case FinalPayment = 'final payment';
    case ReschduleFee = 'reschedule fee';
    case Refund = 'refund';
}
