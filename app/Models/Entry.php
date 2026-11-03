<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'task_id', 'type',
        'content', 'category_tag', 'resource_url',
        'duration_minutes', 'happened_at',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public const TYPES = ['thought', 'dream', 'learn', 'do', 'idea', 'resource'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInDateRange($query, $from, $to)
    {
        return $query->whereBetween('happened_at', [$from, $to]);
    }
}
