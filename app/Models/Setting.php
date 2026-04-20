<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'check_in_time',
        'check_out_time',
        'late_tolerance',
        'early_leave_tolerance',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'late_tolerance' => 'integer',
            'early_leave_tolerance' => 'integer',
        ];
    }

    public static function current(): self
    {
        $setting = static::query()->first();

        if ($setting !== null) {
            return $setting;
        }

        return static::query()->create([
            'check_in_time' => '07:00:00',
            'check_out_time' => '15:00:00',
            'late_tolerance' => 0,
            'early_leave_tolerance' => 0,
        ]);
    }
}
