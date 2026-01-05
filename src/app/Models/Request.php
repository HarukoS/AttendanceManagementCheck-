<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'user_id',
        'work_id',
        'status',
        'reason',
        'requested_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at'  => 'datetime',
        'new_time' => 'array',
    ];

    // 申請者
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 対象勤怠
    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    // 承認者（管理者）
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // 変更内容
    public function details()
    {
        return $this->hasMany(RequestDetail::class);
    }
}
