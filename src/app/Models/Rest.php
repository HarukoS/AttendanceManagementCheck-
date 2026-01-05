<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rest extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_id',
        'rest_start',
        'rest_end'
    ];

    protected $casts = [
        'rest_start' => 'datetime',
        'rest_end'   => 'datetime',
    ];

    protected $dates = ['rest_start', 'rest_end'];

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function getMinutes()
    {
        if (!$this->rest_start || !$this->rest_end) {
            return 0;
        }

        return \Carbon\Carbon::parse($this->rest_end)
            ->diffInMinutes(\Carbon\Carbon::parse($this->rest_start));
    }
}
