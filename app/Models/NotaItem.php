<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotaItem extends Model
{
    use HasFactory;

    protected $table = 'nota_items';

    protected $fillable = [
        'nota_penjualan_id',
        'product_id',
        'quantity',
        'harga',
        'diskon',
        'satuan',
        'keterangan_produk',
        'pajak',
        'jumlah',
    ];

    
    // Relasi ke Nota Penjualan
    public function nota()
    {
        return $this->belongsTo(NotaPenjualan::class, 'nota_penjualan_id');
    }


     //Relasi ke Produk
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
