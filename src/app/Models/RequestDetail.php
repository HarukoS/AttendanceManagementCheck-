<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestDetail extends Model
{
    protected $fillable = [
        'request_id',
        'type',
        'work_id',
        'rest_id',
        'old_time',
        'new_time',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function rest()
    {
        return $this->belongsTo(Rest::class);
    }
}
