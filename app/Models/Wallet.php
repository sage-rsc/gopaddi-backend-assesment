<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function sentTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'sender_wallet_id');
    }

    public function receivedTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'receiver_wallet_id');
    }
}
