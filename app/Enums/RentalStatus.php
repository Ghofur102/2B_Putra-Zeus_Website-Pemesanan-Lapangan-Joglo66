<?php

namespace App\Enums;

enum RentalStatus: string
{
    case BORROWED = 'dipinjam';
    case RETURNED = 'dikembalikan';
    case OVERDUE = 'terlambat';
}
