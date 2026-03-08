<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingDetail extends Model
{
    protected $connection = "mysql_joglo66_app";
    protected $table = "booking_details";
    public $fillable = [
        "fk_booking_id", "start_time", "end_time", "play_date", "price", "status"
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'fk_booking_id', 'id');
    }
    public function bookingReschedule(): HasMany
    {
        return $this->hasMany(BookingReschedule::class, 'fk_booking_reschedule_id', 'id');
    }
}
