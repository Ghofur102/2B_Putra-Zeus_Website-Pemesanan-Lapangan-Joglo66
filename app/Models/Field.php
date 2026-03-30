<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Field extends Model
{
    protected $connection = 'mysql_joglo66_app';

    protected $table = 'fields';

    protected $fillable = [
        'name', 'description', 'image_url', 'category', 'start_time', 'close_time',
    ];

    public function fieldPrices(): HasMany
    {
        return $this->hasMany(FieldPrice::class, 'fk_field_id', 'id');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class, 'fk_filed_id', 'id');
    }

    public function bookingDetail(): HasMany
    {
        return $this->hasMany(BookingDetail::class, 'fk_booking_detail_id', 'id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'fk_field_id', 'id');
    }

    public function financialReport(): HasMany
    {
        return $this->hasMany(FinancialReport::class, 'fk_field_id', 'id');
    }

    public function fieldAdmin(): HasMany
    {
        return $this->hasMany(FieldAdmin::class, 'fk_field_id', 'id');
    }
}
