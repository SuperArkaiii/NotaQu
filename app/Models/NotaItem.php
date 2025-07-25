<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotaItem extends Model
{
    use HasFactory;

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

    public function nota()
    {
        return $this->belongsTo(NotaPenjualan::class, 'nota_penjualan_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
