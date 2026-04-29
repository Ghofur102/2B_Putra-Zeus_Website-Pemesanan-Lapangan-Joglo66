<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $connection = 'mysql_joglo66_app';
    protected $table = 'payments';

    protected $fillable = [
        'fk_booking_id', 'fk_booking_detail_id', 'reference_id', 'payment_url', 'payment_type', 'method', 'amount', 'status', 'paid_at',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'fk_booking_id', 'id');
    }

    public function bookingDetail(): BelongsTo
    {
        return $this->belongsTo(BookingDetail::class, 'fk_booking_detail_id', 'id');
    }
}
