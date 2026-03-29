<?php

namespace App\Enums;

enum StatusDetailBookingEnum: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
}
