<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Bisa pilih salah satu, fillable atau guarded.
    protected $fillable = [
        'nama_produk',
        'stok',
        'harga',
        'status',
        // tambah field lain sesuai kolom di tabel products
    ];

    /**
     * Update status otomatis berdasarkan stok
     */
    protected static function booted()
    {
        static::saving(function ($product) {
            $product->status = $product->stok > 0 ? 'Tersedia' : 'Habis';
        });
    }

    /**
     * Relasi ke NotaItem (produk yang terjual)
     */
    public function notaItems()
    {
        return $this->hasMany(NotaItem::class, 'product_id');
    }


    //relasi stock history
    public function stockHistories()
    {
        return $this->hasMany(StockHistory::class);
    } 

}
