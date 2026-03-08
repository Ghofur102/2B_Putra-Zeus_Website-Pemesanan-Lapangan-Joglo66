<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    public $timestamps = false;
    protected $connection = "mysql_joglo66_app";
    protected $table = "logs";
    protected $fillable = [
        'fk_user_id',
        'action',
        'table_name',
        'record_id',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_user_id', 'id');
    }
}
