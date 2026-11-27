<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'case_id',
        'assigned_to',
        'created_by',
        'title',
        'description',
        'due_at',
        'priority',
        'status',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
