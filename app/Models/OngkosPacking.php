<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OngkosPacking extends Model
{
    use HasFactory;
    protected $guarded=[];

    protected static function booted()
{
    static::creating(function ($model) {
        $model->harga = $model->jumlah_koli * 100000;
    });
}
}
