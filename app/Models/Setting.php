<?php

namespace App\Models;

use Carbon\Carbon;
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
        'monday_check_in_time',
        'monday_check_out_time',
        'tuesday_check_in_time',
        'tuesday_check_out_time',
        'wednesday_check_in_time',
        'wednesday_check_out_time',
        'thursday_check_in_time',
        'thursday_check_out_time',
        'friday_check_in_time',
        'friday_check_out_time',
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
            'monday_check_in_time' => '07:00:00',
            'monday_check_out_time' => '15:00:00',
            'tuesday_check_in_time' => '07:00:00',
            'tuesday_check_out_time' => '15:00:00',
            'wednesday_check_in_time' => '07:00:00',
            'wednesday_check_out_time' => '15:00:00',
            'thursday_check_in_time' => '07:00:00',
            'thursday_check_out_time' => '15:00:00',
            'friday_check_in_time' => '07:00:00',
            'friday_check_out_time' => '15:00:00',
            'late_tolerance' => 0,
            'early_leave_tolerance' => 0,
        ]);
    }

    public function isSchoolDay(Carbon $date): bool
    {
        return $date->isWeekday();
    }

    public function checkInTimeFor(Carbon $date): ?string
    {
        $prefix = $this->schedulePrefixFor($date);

        if ($prefix === null) {
            return null;
        }

        $column = $prefix.'_check_in_time';

        return $this->{$column} ?? $this->check_in_time;
    }

    public function checkOutTimeFor(Carbon $date): ?string
    {
        $prefix = $this->schedulePrefixFor($date);

        if ($prefix === null) {
            return null;
        }

        $column = $prefix.'_check_out_time';

        return $this->{$column} ?? $this->check_out_time;
    }

    private function schedulePrefixFor(Carbon $date): ?string
    {
        return match ($date->isoWeekday()) {
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            default => null,
        };
    }
}
