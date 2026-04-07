<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingDetail extends Model
{
    protected $connection = 'mysql_joglo66_app';

    protected $table = 'booking_details';

    protected $fillable = [
        'fk_booking_id',
        'start_play_time',
        'end_play_time',
        'play_date',
        'price',
        'status',
    ];

    public static function isBookingDetailConflict($detail)
    {
        return BookingDetail::where('fk_field_id', $detail['fk_field_id'])
            ->where('play_date', $detail['play_date'])
            ->where(function ($query) use ($detail) {
                $query->whereBetween('start_play_time', [$detail['start_play_time'], $detail['end_play_time']])
                    ->orWhereBetween('end_play_time', [$detail['start_play_time'], $detail['end_play_time']])
                    ->orWhere(function ($q) use ($detail) {
                        $q->where('start_play_time', '<=', $detail['start_play_time'])
                            ->where('end_play_time', '>=', $detail['end_play_time']);
                    });
            })
            ->exists();
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'fk_booking_id', 'id');
    }

    public function payment(): HasMany
    {
        return $this->hasMany(Payment::class, 'fk_booking_detail_id', 'id');
    }

    public function bookingReschedule(): HasMany
    {
        return $this->hasMany(BookingReschedule::class, 'fk_booking_reschedule_id', 'id');
    }

    public function bookingCancelled(): HasMany
    {
        return $this->hasMany(BookingCancle::class, 'fk_booking_cancle_id', 'id');
    }
}
