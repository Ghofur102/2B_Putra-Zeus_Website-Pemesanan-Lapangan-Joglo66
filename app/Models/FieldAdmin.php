<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FieldAdmin extends Model
{
    protected $connection = "mysql_joglo66_app";
    protected $table = "field_admins";
    public $fillable = [
        "field_id", "user_id"
    ];

    public function field(): BelongsToMany {
        return $this->belongsToMany(Field::class, 'fk_field_id', 'id');
    }
    public function user(): BelongsToMany {
        return $this->belongsToMany(User::class, 'fk_user_id', 'id');
    }
}
