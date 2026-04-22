<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nis',
        'fingerprint_id',
        'face_descriptor',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fingerprint_id' => 'integer',
            'face_descriptor' => 'array',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
