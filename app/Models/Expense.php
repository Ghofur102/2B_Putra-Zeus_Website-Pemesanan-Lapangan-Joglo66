<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    protected $connection = 'mysql_joglo66_app';

    protected $table = 'expenses';

    protected $fillable = [
        'fk_field_id', 'fk_user_id', 'category', 'amount', 'expense_date', 'proof_photo', 'generate_at',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'fk_field_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_user_id', 'id');
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class, 'fk_expense_id', 'id');
    }
}
