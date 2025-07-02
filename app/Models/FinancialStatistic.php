<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'month',
        'category',
        'transaction_type',
        'total_amount',
        'transaction_count',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'year' => 'integer',
        'month' => 'integer',
        'transaction_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
