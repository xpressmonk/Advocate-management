<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'case_id',
        'recorded_by',
        'title',
        'description',
        'category',
        'vendor',
        'amount',
        'currency',
        'expense_date',
        'payment_method',
        'reference',
        'reimbursable',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'reimbursable' => 'boolean',
        ];
    }

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
