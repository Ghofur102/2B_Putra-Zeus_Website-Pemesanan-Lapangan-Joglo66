<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Field extends Model
{
    protected $connection = "mysql_joglo66_app";
    protected $table = "fields";
    public $fillable = [
        "name", "description", "image_url", "category"
    ];

    public function fieldPrices(): HasMany
    {
        return $this->hasMany(FieldPrice::class, 'fk_field_id', 'id');
    }
    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class, 'fk_filed_id', 'id');
    }
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'fk_field_id', 'id');
    }
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'fk_field_id', 'id');
    }
    public function financialReport(): HasMany
    {
        return $this->hasMany(FinancialReport::class, 'fk_field_id', 'id');
    }
}
