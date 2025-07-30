<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesTrendReport extends ChartWidget
{
    protected static ?string $heading = 'Trend Penjualan';

    // Atur default filter
    public ?string $filter = 'monthly';

    protected static ?string $maxHeight = '350px'; // tinggi chart

    protected function getFilters(): ?array
    {
        return [
            'monthly' => 'Per Bulan',
            'yearly'  => 'Per Tahun',
        ];
    }

    protected function getData(): array
    {
        $labels = [];
        $grossData = [];
        $revenueData = [];

        if ($this->filter === 'monthly') {
            // Ambil data per bulan tahun ini
            $sales = DB::table('nota_penjualans')
                ->selectRaw('MONTH(tanggal) as bulan, SUM(total) as gross, SUM(subtotal) as revenue')
                ->whereYear('tanggal', Carbon::now()->year)
                ->groupBy('bulan')
                ->orderBy('bulan')
                ->get()
                ->keyBy('bulan');

            for ($i = 1; $i <= 12; $i++) {
                $labels[] = Carbon::create()->month($i)->translatedFormat('F');
                $grossData[] = $sales[$i]->gross ?? 0;
                $revenueData[] = $sales[$i]->revenue ?? 0;
            }
        } else {
            // Ambil data per tahun (5 tahun terakhir)
            $sales = DB::table('nota_penjualans')
                ->selectRaw('YEAR(tanggal) as tahun, SUM(total) as gross, SUM(subtotal) as revenue')
                ->groupBy('tahun')
                ->orderBy('tahun')
                ->get()
                ->keyBy('tahun');

            $startYear = Carbon::now()->year - 4;
            $endYear = Carbon::now()->year;

            for ($i = $startYear; $i <= $endYear; $i++) {
                $labels[] = $i;
                $grossData[] = $sales[$i]->gross ?? 0;
                $revenueData[] = $sales[$i]->revenue ?? 0;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Gross Revenue',
                    'data' => $grossData,
                    'borderColor' => '#f59e0b', // oranye
                    'backgroundColor' => 'rgba(245,158,11,0.2)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Revenue',
                    'data' => $revenueData,
                    'borderColor' => '#10b981', // hijau
                    'backgroundColor' => 'rgba(16,185,129,0.2)',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full'; // bikin widget selebar 1 baris
    }
}
