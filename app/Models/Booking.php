<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $connection = "mysql_joglo66_app";
    protected $table = "bookings";
    public $fillable = [
        "fk_user_id", "booking_date", "status_booking"
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_user_id', 'id');
    }
    public function details(): HasMany
    {
        return $this->hasMany(BookingDetail::class, 'fk_booking_id', 'id');
    }
    public function attributes(): HasMany
    {
        return $this->hasMany(BookingAttribute::class, 'fk_booking_id', 'id');
    }
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'fk_booking_id', 'id');
    }
}
