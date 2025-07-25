<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class NotaPenjualan extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_faktur',
        'data_pelanggan_id',
        'tanggal',
        'jatuh_tempo',
        'biaya_kirim',
        'tanggal_kirim',
        'harga',
        'nomor_po',
        'ongkos_packing_id', // <- tambahkan ini
        'keterangan',
        'jumlah_koli',
        'biaya_packing',        
        'subtotal',           
        'dpp',     
        'ppn',                
        'total',              
    ];

    public function items()
    {
        return $this->hasMany(NotaItem::class, 'nota_penjualan_id');
    }

    protected static function booted()
    {
        static::created(function ($nota) {
            foreach ($nota->items as $item) {
                $product = $item->product;
                if ($product) {
                    $product->stok -= $item->quantity;
                    $product->status = $product->stok > 0 ? 'Tersedia' : 'Habis';
                    $product->save();
                }
            }
        });
    }

    protected $casts = [
        'tanggal' => 'date',
        'jatuh_tempo' => 'date',
    ];

    public function dataPelanggan()
    {
        return $this->belongsTo(\App\Models\DataPelanggan::class);
    }

    public function ongkosPacking()
    {
        return $this->belongsTo(\App\Models\OngkosPacking::class);
    }
}
