<?php

namespace App\Enums;

enum BookingDetailStatus: string
{
    case ACTIVE = 'active';
    case WAITING = 'waiting';
    case FINISH = 'finish';
    case CANCELLED = 'cancelled';
    case RESCHEDULE = 'reschedule';
    case FIELD_CLOSURE = 'field closure';
    case CLOSED_FIELD_CANCELLED = 'closed field cancelled';
    case CLOSED_FIELD_RESCHEDULE = 'closed field reschedule';
}
