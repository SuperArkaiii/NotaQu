<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'jumlah',
        'tipe',
        'keterangan',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    protected static function booted()
    {
        static::deleting(function ($history) {
            $product = $history->product;

            if ($history->tipe === 'tambah') {
                // kalau riwayatnya tambah, maka saat dihapus stok harus dikurangi
                $product->stok -= $history->jumlah;
            } elseif ($history->tipe === 'kurang') {
                // kalau riwayatnya kurang, maka saat dihapus stok harus ditambah
                $product->stok += $history->jumlah;
            }

            $product->save();
        });
    }

}

