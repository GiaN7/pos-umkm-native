<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'stok_sebelum',
        'stok_sesudah',
        'selisih',
        'tipe',
        'keterangan',
        'user_id',
    ];

    protected $casts = [
        'stok_sebelum' => 'integer',
        'stok_sesudah' => 'integer',
        'selisih' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
