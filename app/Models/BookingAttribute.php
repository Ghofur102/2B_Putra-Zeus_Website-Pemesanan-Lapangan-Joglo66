<?php

namespace App\Models;

use App\Models\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAttribute extends Model
{
    protected $connection = 'mysql_joglo66_app';

    protected $table = 'booking_attributes';

    protected $fillable = [
        'fk_booking_id', 'fk_attribute_id', 'quantity', 'price', 'total',
        'transaction_date', 'status', 'customer_name', 'customer_phone',
        'duration_hours', 'reason',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'fk_booking_id', 'id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'fk_attribute_id', 'id');
    }
}
