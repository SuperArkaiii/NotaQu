<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CostReport extends ChartWidget
{
    protected static ?string $heading = 'Ringkasan Biaya';

    protected function getFilters(): ?array
    {
        return [
            'this_month' => 'Bulan Ini',
            'this_year'  => 'Tahun Ini',
        ];
    }

    protected function getData(): array
    {
        $query = DB::table('nota_penjualans');
        $label = ''; // label dinamis

        switch ($this->filter) {
            case 'this_month':
                $query->whereMonth('tanggal', Carbon::now()->month)
                      ->whereYear('tanggal', Carbon::now()->year);
                $label = Carbon::now()->locale('id')->translatedFormat('F Y'); 
                // Contoh: Juli 2025
                break;

            case 'this_year':
                $query->whereYear('tanggal', Carbon::now()->year);
                $label = Carbon::now()->year; 
                // Contoh: 2025
                break;

            case 'last_year':
                $query->whereYear('tanggal', Carbon::now()->subYear()->year);
                $label = Carbon::now()->subYear()->year; 
                // Contoh: 2024
                break;

            default:
                $label = 'Data';
                break;
        }

        $biayaKirim = $query->sum('biaya_kirim');
        $biayaPacking = $query->sum('biaya_packing');

        return [
            'datasets' => [
                [
                    'label' => 'Biaya Kirim',
                    'data' => [$biayaKirim],
                    'backgroundColor' => '#3b82f6',
                ],
                [
                    'label' => 'Biaya Packing',
                    'data' => [$biayaPacking],
                    'backgroundColor' => '#ef4444',
                ],
            ],
            'labels' => [$label], // label sesuai filter
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
