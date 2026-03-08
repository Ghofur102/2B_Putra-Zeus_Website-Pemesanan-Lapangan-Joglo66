<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingReschedule extends Model
{
    protected $connection = "mysql_joglo66_app";
    protected $table = "booking_reschedules";
    public $fillable = [
        "fk_booking_detail_id", "old_date", "new_date", "type_reschedule"
    ];

    public function bookingDetail(): BelongsTo
    {
        return $this->belongsTo(BookingDetail::class, 'fk_booking_detail_id', 'id');
    }
}
