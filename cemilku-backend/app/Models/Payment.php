<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'method', 'status', 'amount', 'proof_image', 'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    protected $appends = [
        'proof_image_url',
    ];

    public function getProofImageUrlAttribute(): ?string
    {
        if (!$this->proof_image) {
            return null;
        }

        if (str_starts_with($this->proof_image, 'http://') || str_starts_with($this->proof_image, 'https://')) {
            return $this->proof_image;
        }

        return url('storage/' . ltrim($this->proof_image, '/'));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
