<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subacquirer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'base_url',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function pixTransactions(): HasMany
    {
        return $this->hasMany(PixTransaction::class);
    }

    public function withdrawTransactions(): HasMany
    {
        return $this->hasMany(WithdrawTransaction::class);
    }
}
