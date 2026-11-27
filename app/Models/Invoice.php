<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'client_id',
        'case_id',
        'created_by',
        'issue_date',
        'due_date',
        'status',
        'currency',
        'subtotal',
        'tax_total',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function paidAmount(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balanceDue(): float
    {
        return max(0, (float) $this->total - $this->paidAmount());
    }

    public function refreshStatus(): void
    {
        $paid = $this->paidAmount();
        $status = $this->status;

        if ($status !== 'cancelled' && $paid >= (float) $this->total && (float) $this->total > 0) {
            $status = 'paid';
        } elseif ($status !== 'cancelled' && $paid > 0) {
            $status = 'partially_paid';
        } elseif ($status === 'paid' || $status === 'partially_paid') {
            $status = 'sent';
        }

        $this->status = $status;
        $this->save();
    }
}
