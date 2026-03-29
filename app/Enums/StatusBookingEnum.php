<?php

namespace App\Enums;

enum StatusBookingEnum: string
{
    case Active = 'active';
    case Finish = 'finish';
    case Waiting = 'waiting';
}
