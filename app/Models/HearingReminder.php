<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HearingReminder extends Model
{
    protected $fillable = [
        'hearing_id',
        'channel',
        'recipient',
        'scheduled_for',
        'sent_at',
        'status',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function hearing()
    {
        return $this->belongsTo(Hearing::class);
    }
}
