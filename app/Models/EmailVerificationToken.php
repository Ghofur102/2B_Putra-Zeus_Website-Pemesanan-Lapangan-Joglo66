<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailVerificationToken extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'token',
        'type',
        'created_at',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    protected $connection = 'mysql_joglo66_app';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Check if token is already used
     */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * Check if token is valid (not expired and not used)
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed();
    }

    /**
     * Mark token as used
     */
    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    /**
     * Scope to get only verify tokens
     */
    public function scopeVerifyTokens($query)
    {
        return $query->where('type', 'verify');
    }

    /**
     * Scope to get only reset tokens
     */
    public function scopeResetTokens($query)
    {
        return $query->where('type', 'reset');
    }

    /**
     * Scope to get only valid tokens
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
                    ->whereNull('used_at');
    }
}
