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
    protected static ?string $maxHeight = '250px'; // tinggi chart


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
        $data = [];

        if ($this->filter === 'monthly') {
            // Ambil data per bulan tahun ini
            $sales = DB::table('nota_penjualans')
                ->selectRaw('MONTH(tanggal) as bulan, SUM(total) as total')
                ->whereYear('tanggal', Carbon::now()->year)
                ->groupBy('bulan')
                ->orderBy('bulan')
                ->pluck('total', 'bulan');

            for ($i = 1; $i <= 12; $i++) {
                $labels[] = Carbon::create()->month($i)->translatedFormat('F'); // Januari, Februari...
                $data[] = $sales[$i] ?? 0;
            }
        } else {
            // Ambil data per tahun (5 tahun terakhir)
            $sales = DB::table('nota_penjualans')
                ->selectRaw('YEAR(tanggal) as tahun, SUM(total) as total')
                ->groupBy('tahun')
                ->orderBy('tahun')
                ->pluck('total', 'tahun');

            $startYear = Carbon::now()->year - 4;
            $endYear = Carbon::now()->year;

            for ($i = $startYear; $i <= $endYear; $i++) {
                $labels[] = $i;
                $data[] = $sales[$i] ?? 0;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59,130,246,0.2)',
                    'tension' => 0.4, // bikin garis agak melengkung
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
