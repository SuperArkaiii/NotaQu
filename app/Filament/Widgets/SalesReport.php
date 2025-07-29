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

        //filter
        switch ($this->filter) {
            case 'this_month':
                $query->whereMonth('tanggal', Carbon::now()->month)
                      ->whereYear('tanggal', Carbon::now()->year);
                break;

            case 'this_year':
                $query->whereYear('tanggal', Carbon::now()->year);
                break;

            case 'last_year':
                $query->whereYear('tanggal', Carbon::now()->subYear()->year);
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
            'labels' => ['Penjualan'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
