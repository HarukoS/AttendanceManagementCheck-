<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Rest;

class Work extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'work_start',
        'work_end',
    ];

    protected $casts = [
        'date' => 'date',
        'work_start' => 'datetime',
        'work_end'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rests()
    {
        return $this->hasMany(Rest::class);
    }

    // 勤務時間（分）
    public function getWorkMinutes(): int
    {
        if (!$this->work_start || !$this->work_end) {
            return 0;
        }

        return Carbon::parse($this->work_end)
            ->diffInMinutes(Carbon::parse($this->work_start));
    }

    // 休憩時間（分）
    public function getRestMinutes(): int
    {
        return $this->rests->sum(function ($rest) {
            if (!$rest->rest_start || !$rest->rest_end) {
                return 0;
            }

            return Carbon::parse($rest->rest_end)
                ->diffInMinutes(Carbon::parse($rest->rest_start));
        });
    }

    // 実働時間（分）
    public function getActualMinutes(): int
    {
        return $this->getWorkMinutes() - $this->getRestMinutes();
    }

    // 休憩時間（hh:mm）
    public function getRestTimeAttribute(): string
    {
        $minutes = $this->getRestMinutes();

        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }

    // 実働時間（hh:mm）
    public function getActualTimeAttribute(): string
    {
        $minutes = $this->getActualMinutes();

        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function details()
    {
        return $this->hasMany(RequestDetail::class);
    }
}
