<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldPrice extends Model
{
    protected $connection = "mysql_joglo66_app";
    protected $table = "field_prices";
    public $fillable = [
        "fk_field_id", "start_time", "end_time", "day_type", "price"
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'fk_field_id', 'id');
    }
}
