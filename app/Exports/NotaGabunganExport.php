<?php

namespace App\Exports;

use App\Models\NotaPenjualan;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotaGabunganExport
{
    protected Collection $notas;

    public function __construct(Collection $notas)
    {
        $this->notas = $notas;
    }

    public function download(): StreamedResponse
    {
        $templatePath = storage_path('app/templates/Format .xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $startRow = 30; // Baris awal untuk data barang
        $currentRow = $startRow;

        // Gabungkan semua items dari semua nota
        $allItems = collect();
        $totalKoli = 0;
        $firstNota = $this->notas->first();

        foreach ($this->notas as $nota) {
            $items = $nota->items;
            $allItems = $allItems->merge($items);
            $totalKoli += $items->count();
        }

        // Insert additional rows untuk data barang saja
        $this->insertRowsForItems($sheet, $allItems->count());

        // Header - sesuai dengan struktur yang Anda berikan
        $sheet->setCellValue("O6", $firstNota->kode_faktur . '/2025/INV/RPN/05');
        $sheet->setCellValue("O7", $firstNota->tanggal->format('d F Y'));
        $sheet->setCellValue("E20", $firstNota->dataPelanggan->nama ?? '-');
        $sheet->setCellValue("E22", $firstNota->dataPelanggan->alamat ?? '-');
        $sheet->setCellValue("O20", $firstNota->jatuh_tempo->format('d F Y'));
        
        // No. Penawaran harus disesuaikan dengan jumlah baris yang ditambahkan
        $totalItems = $allItems->count();
        $itemsAdded = $totalItems > 1 ? $totalItems - 1 : 0;
        $basePenawaranRow = 45; // Posisi dasar No. Penawaran
        $penawaranRow = $basePenawaranRow + $itemsAdded;
        $sheet->setCellValue("A{$penawaranRow}", "No. Penawaran:" . $firstNota->kode_faktur . '/INVRPN/05');

        // Item Table - Fill data barang
        $row = $currentRow;
        $subtotal = 0;
        $no = 1;

        foreach ($allItems as $item) { 
            $jumlah = $item->harga * $item->quantity;

            $sheet->setCellValue("A{$row}", $no++); // NO.
            
            // Keterangan barang (B:G merged) - set di B saja karena merged
            $sheet->setCellValue("B{$row}", $item->product->nama_produk ?? '-'); // Barang (B-G merged)
            $sheet->mergeCells("B{$row}:G{$row}"); // Merge B-G untuk keterangan barang
            
            $sheet->setCellValue("H{$row}", $item->quantity); // Quantity
            $sheet->setCellValue("I{$row}", "brng"); // Keterangan quantity
            
            // Harga satuan (J:K merged) - set di J saja karena merged
            $sheet->setCellValue("J{$row}", $item->harga); // HARGA SATUAN (J-K merged)
            $sheet->mergeCells("J{$row}:K{$row}"); // Merge J-K untuk harga satuan
            
            // Diskon (L:M merged) - set di L saja karena merged
            $sheet->setCellValue("L{$row}", "0,0"); // Diskpn (L-M merged)
            $sheet->mergeCells("L{$row}:M{$row}"); // Merge L-M untuk diskon
            
            $sheet->setCellValue("N{$row}", "X"); // Pajak (kolom N)
            $sheet->setCellValue("O{$row}", $jumlah); // JUMLAH

            // Format currency untuk harga dan jumlah
            $sheet->getStyle("J{$row}:K{$row}")->getNumberFormat()->setFormatCode('"Rp" #,##0_-');
            $sheet->getStyle("O{$row}")->getNumberFormat()->setFormatCode('"Rp" #,##0_-');

            $subtotal += $jumlah;
            $row++;
        }

        // Hitung posisi perhitungan berdasarkan jumlah item yang ditambahkan
        $totalItems = $allItems->count();
        $itemsAdded = $totalItems > 1 ? $totalItems - 1 : 0; // Jumlah baris yang ditambahkan

        // Posisi perhitungan - disesuaikan dengan jumlah baris yang ditambahkan
        $baseCalculationRow = 31; // Posisi dasar untuk subtotal (template asli)
        $subtotalRow = $baseCalculationRow + $itemsAdded;
        $ppnRow = $subtotalRow + 1;
        $dppRow = $subtotalRow + 2;
        $packingRow = $subtotalRow + 3;
        $kirimRow = $subtotalRow + 4;
        $totalRow1 = $subtotalRow + 5;
        $totalRow2 = $subtotalRow + 6;
        
        // Posisi terbilang juga disesuaikan
        $baseTerbilangRow = 58; // Posisi dasar terbilang (template asli)
        $terbilangRow = $baseTerbilangRow + $itemsAdded;

        // Hitung total sesuai dengan struktur yang Anda berikan
        $biaya_packing = $totalKoli * 100000; // Biaya packing = jumlah barang × 100,000
        $biaya_kirim = $firstNota->biaya_kirim ?? 0;
        $dpp_nilai_lain = $subtotal * 11 / 12; // DPP Nilai Lain = Subtotal × 11/12
        $ppn = $dpp_nilai_lain * 0.12; // PPN 12% = 12% × DPP Nilai Lain
        $total = $subtotal + $ppn + $biaya_packing + $biaya_kirim;

        // Set nilai dan formula sesuai struktur
        $sheet->setCellValue("O{$subtotalRow}", $subtotal); // Subtotal
        $sheet->setCellValue("O{$ppnRow}", $ppn); // PPN 12%
        $sheet->setCellValue("O{$dppRow}", $dpp_nilai_lain); // DPP Nilai Lain
        $sheet->setCellValue("O{$packingRow}", $biaya_packing); // Biaya Packing
        $sheet->setCellValue("O{$kirimRow}", $biaya_kirim); // Biaya Kirim
        $sheet->setCellValue("O{$totalRow1}", $total); // TOTAL
        $sheet->setCellValue("O{$totalRow2}", $total); // TOTAL (duplikat)
        $sheet->setCellValue("A{$terbilangRow}", $this->terbilang($total)); // TERBILANG

        // Format currency untuk semua nilai total
        $totalCells = ["O{$subtotalRow}", "O{$ppnRow}", "O{$dppRow}", "O{$packingRow}", "O{$kirimRow}", "O{$totalRow1}", "O{$totalRow2}"];
        foreach ($totalCells as $cell) {
            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('"Rp" #,##0_-');
        }

        // Set label untuk setiap perhitungan (merge kolom L:M)
        $sheet->setCellValue("L{$subtotalRow}", "Subtotal");
        $sheet->mergeCells("L{$subtotalRow}:M{$subtotalRow}");
        
        $sheet->setCellValue("L{$ppnRow}", "PPN 12%");
        $sheet->mergeCells("L{$ppnRow}:M{$ppnRow}");
        
        $sheet->setCellValue("L{$dppRow}", "DPP Nilai Lain");
        $sheet->mergeCells("L{$dppRow}:M{$dppRow}");
        
        $sheet->setCellValue("L{$packingRow}", "Biaya Packing");
        $sheet->mergeCells("L{$packingRow}:M{$packingRow}");
        
        $sheet->setCellValue("L{$kirimRow}", "Biaya Kirim");
        $sheet->mergeCells("L{$kirimRow}:M{$kirimRow}");
        
        $sheet->setCellValue("L{$totalRow1}", "TOTAL");
        $sheet->mergeCells("L{$totalRow1}:M{$totalRow1}");
        
        $sheet->setCellValue("L{$totalRow2}", "Sisa Tagihan");
        $sheet->mergeCells("L{$totalRow2}:M{$totalRow2}");

        // Add styling untuk area perhitungan (tanpa border)
        $this->addCalculationStyling($sheet, $subtotalRow, $totalRow2);

        // Style untuk tabel barang
        $this->addItemTableStyling($sheet, $startRow, $startRow + $totalItems - 1);

        $writer = new Xlsx($spreadsheet);
        $kode_faktur = str_replace(['/', '\\', ':'], '-', $firstNota->kode_faktur);
        $filename = "Invoice{$kode_faktur}.xlsx";
        
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename);
        
        
    }

    protected function addCalculationStyling($sheet, $startRow, $endRow)
    {
        // Styling untuk area perhitungan tanpa border
        $styleArray = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        
        // Apply styling tanpa border untuk area perhitungan
        $sheet->getStyle("L{$startRow}:O{$endRow}")->applyFromArray($styleArray);
        
        // Alignment khusus untuk label (L:M) - rata kiri
        $sheet->getStyle("L{$startRow}:M{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        
        // Alignment khusus untuk nilai (kolom O) - rata kanan
        $sheet->getStyle("O{$startRow}:O{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        
        // Bold untuk total rows
        $sheet->getStyle("L{$endRow}:O{$endRow}")->getFont()->setBold(true);
    }

    protected function addItemTableStyling($sheet, $startRow, $endRow)
    {
        // Style untuk tabel barang (border dan alignment)
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        
        // Apply styling untuk seluruh range tabel barang
        $sheet->getStyle("A{$startRow}:O{$endRow}")->applyFromArray($styleArray);
        
        // Text alignment untuk kolom tertentu
        $sheet->getStyle("A{$startRow}:A{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // NO
        $sheet->getStyle("B{$startRow}:G{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Barang (B-G merged)
        $sheet->getStyle("H{$startRow}:H{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Quantity
        $sheet->getStyle("I{$startRow}:I{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // brng
        $sheet->getStyle("J{$startRow}:K{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT); // Harga Satuan (J-K merged)
        $sheet->getStyle("L{$startRow}:M{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Diskon (L-M merged)
        $sheet->getStyle("N{$startRow}:N{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Pajak
        $sheet->getStyle("O{$startRow}:O{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT); // Jumlah
    }

    protected function insertRowsForItems($sheet, $totalItems)
    {
        // Hanya insert rows jika ada lebih dari 1 item
        if ($totalItems > 1) {
            $additionalRows = $totalItems - 1;
            
            // Insert rows hanya untuk area data barang
            // Dimulai dari baris 31 (setelah baris pertama data barang)
            for ($i = 0; $i < $additionalRows; $i++) {
                $sheet->insertNewRowBefore(31, 1);
            }
        }
    }

    protected function terbilang($angka): string
    {
        $f = new \NumberFormatter("id", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($angka)) . ' rupiah';
    }
}