<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    protected $fillable = [
        'household_id',
        'posted_by',
        'paid_by',
        'title',
        'amount',
        'paid_at',
        'payment_method',
        'proof_path',
        'split_type'
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function splits(): HasMany
    {
        return $this->hasMany(ExpenseSplit::class);
    }

    public function paidBy(): BelongsTo
    {
    return $this->belongsTo(User::class, 'paid_by');
    }
}

