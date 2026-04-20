<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
