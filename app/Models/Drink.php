<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Drink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'drink_type', 'drink_name',
        'amount_ml', 'alcohol_pct',
        'pure_alcohol_ml', 'standard_drinks',
        'note', 'location', 'company_count',
        'drank_at',
    ];

    protected $casts = [
        'amount_ml' => 'integer',
        'alcohol_pct' => 'decimal:1',
        'pure_alcohol_ml' => 'decimal:2',
        'standard_drinks' => 'decimal:2',
        'company_count' => 'integer',
        'drank_at' => 'datetime',
    ];

    // 酒类元数据（label + emoji + 默认酒精度）
    public const TYPES = [
        'beer'     => ['label' => '啤酒',   'emoji' => '🍺', 'default_pct' => 5.0],
        'wine'     => ['label' => '红酒',   'emoji' => '🍷', 'default_pct' => 13.0],
        'sake'     => ['label' => '清酒',   'emoji' => '🍶', 'default_pct' => 15.0],
        'soju'     => ['label' => '烧酒',   'emoji' => '🥃', 'default_pct' => 17.0],
        'baijiu'   => ['label' => '白酒',   'emoji' => '🥃', 'default_pct' => 52.0],
        'whisky'   => ['label' => '威士忌', 'emoji' => '🥃', 'default_pct' => 40.0],
        'cocktail' => ['label' => '鸡尾酒', 'emoji' => '🍸', 'default_pct' => 12.0],
        'other'    => ['label' => '其他',   'emoji' => '🍹', 'default_pct' => 10.0],
    ];

    // 1 标准杯 = 10ml 纯酒精（WHO 标准）
    public const ML_PER_STANDARD_DRINK = 10;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
