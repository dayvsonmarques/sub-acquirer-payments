<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookAttempt extends Model
{
    use HasFactory;

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PENDING = 'pending';

    public const SOURCE_SIMULATION = 'simulation';
    public const SOURCE_EXTERNAL = 'external';

    protected $fillable = [
        'transaction_type',
        'transaction_id',
        'status',
        'payload',
        'response',
        'source',
        'error_message',
        'attempt_number',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'attempt_number' => 'integer',
    ];
}
