<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialReport extends Model
{
    protected $connection = "mysql_joglo66_app";
    protected $table = "financial_reports";
    public $fillable = [
        "fk_field_id", "year", "mont", "total_income", "total_expense", "net_profit", "generate_at"
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'fk_field_id', 'id');
    }
}
