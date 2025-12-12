<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'action',
        'entity_type',
        'entity_id',
        'reference',
        'user_id',
        'wallet_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];
    }
}
