<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    public const STATUS_ARRIVED = 'arrived';

    public const STATUS_LATE = 'late';

    public const STATUS_DEPARTED = 'departed';

    public const STATUS_EARLY_LEAVE = 'early_leave';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'date',
        'check_in',
        'check_out',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
        ];
    }

    /**
     * @return array{label: string, color: string}
     */
    private function statusPresentation(): array
    {
        return match ($this->status) {
            self::STATUS_ARRIVED => [
                'label' => 'Hadir Tepat Waktu',
                'color' => 'green',
            ],
            self::STATUS_LATE => [
                'label' => 'Terlambat',
                'color' => 'orange',
            ],
            self::STATUS_DEPARTED => [
                'label' => 'Pulang',
                'color' => 'blue',
            ],
            self::STATUS_EARLY_LEAVE => [
                'label' => 'Pulang Cepat',
                'color' => 'purple',
            ],
            default => [
                'label' => ucwords(str_replace('_', ' ', $this->status)),
                'color' => 'gray',
            ],
        };
    }

    public function statusLabel(): string
    {
        return $this->statusPresentation()['label'];
    }

    public function statusColor(): string
    {
        return $this->statusPresentation()['color'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
