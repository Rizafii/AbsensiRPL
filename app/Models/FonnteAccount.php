<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FonnteAccount extends Model
{
    use HasFactory;

    public const EVENT_CHECK_IN = 'check_in';

    public const EVENT_CHECK_OUT = 'check_out';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'event_type',
        'account_name',
        'base_url',
        'token',
        'parent_group_target',
        'timeout',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'timeout' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function activeForEvent(string $eventType): ?self
    {
        return static::query()
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->first();
    }
}
