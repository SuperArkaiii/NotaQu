<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $guarded=[];

    protected static function booted()
{
    static::saving(function ($produk) {
        $produk->status = $produk->stok > 0 ? 'Tersedia' : 'Habis';
    });
}

}
