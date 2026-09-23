<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ErpNotification — one row in the notification centre.
 *
 * `user_id` NULL = broadcast to everyone (the single-company farm); otherwise it
 * targets one user. Only real ERP events create these (see NotificationService).
 */
class ErpNotification extends Model
{
    protected $table = 'erp_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'url',
        'icon',
        'tone',
        'read_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Notifications visible to a user: broadcast + their own. */
    public function scopeForUser(Builder $query, ?int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId): void {
            $q->whereNull('user_id');
            if ($userId !== null) {
                $q->orWhere('user_id', $userId);
            }
        });
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
