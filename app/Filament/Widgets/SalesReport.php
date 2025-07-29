<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesReport extends ChartWidget
{
    protected static ?string $heading = 'Ringkasan Penjualan';

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
        $label = ''; // Label dinamis

        // Filter
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

        $gross = $query->sum('total');
        $revenue = $query->sum('subtotal');

        return [
            'datasets' => [
                [
                    'label' => 'Gross Revenue',
                    'data' => [$gross],
                    'backgroundColor' => '#f59e0b',
                ],
                [
                    'label' => 'Revenue',
                    'data' => [$revenue],
                    'backgroundColor' => '#10b981',
                ],
            ],
            'labels' => [$label], // label dinamis
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
