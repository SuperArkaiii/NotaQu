<?php

namespace App\Exports;

use App\Models\NotaPenjualan;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

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
        
        // Keterangan di baris 46-47 (disesuaikan dengan jumlah item yang ditambahkan)
        $baseKeteranganRow1 = 45; // Posisi dasar keterangan baris pertama
        $baseKeteranganRow2 = 46; // Posisi dasar keterangan baris kedua
        $keteranganRow1 = $baseKeteranganRow1 + $itemsAdded;
        $keteranganRow2 = $baseKeteranganRow2 + $itemsAdded;
        
        // Set keterangan (merge kolom A sampai J)
        $keterangan = $firstNota->keterangan ?? 'Terima kasih atas kepercayaan Anda';
        $sheet->setCellValue("A{$keteranganRow1}", $keterangan);
        $sheet->mergeCells("A{$keteranganRow1}:J{$keteranganRow1}");
        
        // Baris kedua keterangan (jika diperlukan)
        $sheet->setCellValue("A{$keteranganRow2}", "");
        $sheet->mergeCells("A{$keteranganRow2}:J{$keteranganRow2}");

        // Item Table - Fill data barang
        $row = $currentRow;
        $subtotal = 0;
        $no = 1;

        foreach ($allItems as $item) { 
            // Perhitungan sesuai dengan logika di NotaPenjualanResource
            $harga = (float) $item->harga;
            $qty = (int) $item->quantity;
            $diskonPersen = (float) ($item->diskon ?? 0);
            $pajakPersen = (float) ($item->pajak ?? 0);

            // Hitung subtotal item
            $itemSubtotal = $harga * $qty;
            
            // Hitung diskon sebagai persen dari subtotal item
            $itemDiskon = $diskonPersen > 0 ? $itemSubtotal * ($diskonPersen / 100) : 0;
            $afterDiskon = $itemSubtotal - $itemDiskon;
            
            // Hitung pajak sebagai persen dari nilai setelah diskon
            $itemPajak = $pajakPersen > 0 ? $afterDiskon * ($pajakPersen / 100) : 0;
            
            // Jumlah akhir per item
            $jumlah = $afterDiskon + $itemPajak;

            $sheet->setCellValue("A{$row}", $no++); // NO.
            
            // Keterangan barang (B:G merged) - set di B saja karena merged
            $sheet->setCellValue("B{$row}", $item->product->nama_produk ?? '-'); // Barang (B-G merged)
            $sheet->mergeCells("B{$row}:G{$row}"); // Merge B-G untuk keterangan barang
            
            $sheet->setCellValue("H{$row}", $item->quantity); // Quantity
            
            // SATUAN - Kolom I (diisi manual/input dari user)
            $sheet->setCellValue("I{$row}", $item->satuan ?? ''); // Satuan dari input user
            
            // Harga satuan (J:K merged) - set di J saja karena merged
            $sheet->setCellValueExplicit("J{$row}", (float)$item->harga, DataType::TYPE_NUMERIC); // HARGA SATUAN (J-K merged)
            $sheet->mergeCells("J{$row}:K{$row}"); // Merge J-K untuk harga satuan
            
            // PERBAIKAN DISKON - Format sesuai kebutuhan manual (0 = 0,0 ; 2 = 2,0)
            $diskonValue = $item->diskon ?? 0;
            // Format diskon dengan 1 desimal menggunakan koma sebagai pemisah
            $diskonFormatted = number_format((float)$diskonValue, 1, ',', '.');
            $sheet->setCellValue("L{$row}", $diskonFormatted); // Diskon sebagai text dengan format manual
            $sheet->mergeCells("L{$row}:M{$row}"); // Merge L-M untuk diskon
            
            $sheet->setCellValue("N{$row}", "X"); // Pajak (kolom N)
            $sheet->setCellValueExplicit("O{$row}", (float)$jumlah, DataType::TYPE_NUMERIC); // JUMLAH

            // Format currency untuk harga dan jumlah dengan 2 desimal
            $sheet->getStyle("J{$row}:K{$row}")->getNumberFormat()->setFormatCode('"Rp" #,##0.00_-');
            // TIDAK perlu format number untuk diskon karena sudah diformat manual sebagai text
            $sheet->getStyle("O{$row}")->getNumberFormat()->setFormatCode('"Rp" #,##0.00_-');

            $subtotal += $jumlah;
            $row++;
        }

        // Hitung posisi perhitungan berdasarkan jumlah item yang ditambahkan
        $totalItems = $allItems->count();
        $itemsAdded = $totalItems > 1 ? $totalItems - 1 : 0; // Jumlah baris yang ditambahkan

        // Posisi perhitungan - disesuaikan dengan jumlah baris yang ditambahkan
        $baseCalculationRow = 31; // Posisi dasar untuk subtotal (template asli)
        $subtotalRow = $baseCalculationRow + $itemsAdded;
        $dppRow = $subtotalRow + 1;        // DPP ditempatkan setelah subtotal
        $ppnRow = $subtotalRow + 2;        // PPN ditempatkan setelah DPP
        $packingRow = $subtotalRow + 3;
        $kirimRow = $subtotalRow + 4;
        $totalRow1 = $subtotalRow + 5;
        $totalRow2 = $subtotalRow + 6;  
        
        // Posisi terbilang juga disesuaikan
        $baseTerbilangRow = 58; // Posisi dasar terbilang (template asli)
        $terbilangRow = $baseTerbilangRow + $itemsAdded;

        // Hitung total sesuai dengan logika di NotaPenjualanResource
        $biaya_packing = $firstNota->biaya_packing ?? 0; // Gunakan nilai dari database
        $biaya_kirim = $firstNota->biaya_kirim ?? 0;
        
        // Hitung DPP (11/12 dari subtotal) - sesuai dengan resource
        $dpp = round($subtotal * (11 / 12), 2);
        
        // Hitung PPN (12% dari DPP) - sesuai dengan resource
        $ppn = round($dpp * 0.12, 2);
        
        // Hitung total akhir - sesuai dengan resource
        $total = round($subtotal + $ppn + $biaya_packing + $biaya_kirim, 2);

        // Set nilai dan formula sesuai struktur dengan format float - URUTAN DIPERBAIKI
        $sheet->setCellValueExplicit("O{$subtotalRow}", (float)$subtotal, DataType::TYPE_NUMERIC); // Subtotal
        $sheet->setCellValueExplicit("O{$dppRow}", (float)$dpp, DataType::TYPE_NUMERIC); // DPP Nilai Lain (baris kedua)
        $sheet->setCellValueExplicit("O{$ppnRow}", (float)$ppn, DataType::TYPE_NUMERIC); // PPN 12% (baris ketiga)
        $sheet->setCellValueExplicit("O{$packingRow}", (float)$biaya_packing, DataType::TYPE_NUMERIC); // Biaya Packing
        $sheet->setCellValueExplicit("O{$kirimRow}", (float)$biaya_kirim, DataType::TYPE_NUMERIC); // Biaya Kirim
        $sheet->setCellValueExplicit("O{$totalRow1}", (float)$total, DataType::TYPE_NUMERIC); // TOTAL
        $sheet->setCellValueExplicit("O{$totalRow2}", (float)$total, DataType::TYPE_NUMERIC); // TOTAL (duplikat)
        $sheet->setCellValue("A{$terbilangRow}", $this->terbilang($total)); // TERBILANG

        // Format currency untuk semua nilai total dengan 2 desimal
        $totalCells = ["O{$subtotalRow}", "O{$dppRow}", "O{$ppnRow}", "O{$packingRow}", "O{$kirimRow}", "O{$totalRow1}", "O{$totalRow2}"];
        foreach ($totalCells as $cell) {
            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('"Rp" #,##0.00_-');
        }

        // Set label untuk setiap perhitungan (merge kolom L:M) - URUTAN DIPERBAIKI
        $sheet->setCellValue("L{$subtotalRow}", "Subtotal");
        $sheet->mergeCells("L{$subtotalRow}:M{$subtotalRow}");
        
        $sheet->setCellValue("L{$dppRow}", "DPP Nilai Lain");    // DPP di baris kedua
        $sheet->mergeCells("L{$dppRow}:M{$dppRow}");
        
        $sheet->setCellValue("L{$ppnRow}", "PPN 12%");           // PPN di baris ketiga
        $sheet->mergeCells("L{$ppnRow}:M{$ppnRow}");
        
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
        
        // Style untuk area keterangan
        $this->addKeteranganStyling($sheet, $keteranganRow1, $keteranganRow2);

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
        $sheet->getStyle("I{$startRow}:I{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Satuan
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
                
                // Setelah insert row, kita perlu copy format dari baris template
                // untuk memastikan merged cells dan styling tetap konsisten
                $this->copyRowFormat($sheet, 30, 31 + $i);
            }
        }
    }

    protected function copyRowFormat($sheet, $sourceRow, $targetRow)
    {
        // Copy format dari baris sumber ke baris target
        // Ini memastikan merged cells dan styling tetap konsisten
        
        // Copy style untuk seluruh baris
        $sheet->duplicateStyle(
            $sheet->getStyle("{$sourceRow}:{$sourceRow}"),
            "A{$targetRow}:O{$targetRow}"
        );
        
        // Pastikan merged cells juga di-copy
        // Merge B-G untuk keterangan barang
        $sheet->mergeCells("B{$targetRow}:G{$targetRow}");
        
        // Merge J-K untuk harga satuan
        $sheet->mergeCells("J{$targetRow}:K{$targetRow}");
        
        // Merge L-M untuk diskon
        $sheet->mergeCells("L{$targetRow}:M{$targetRow}");
    }

    protected function addKeteranganStyling($sheet, $keteranganRow1, $keteranganRow2)
    {
        // Style untuk area keterangan
        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
            'font' => [
                'size' => 10,
            ],
        ];
        
        // Apply styling untuk area keterangan
        $sheet->getStyle("A{$keteranganRow1}:J{$keteranganRow1}")->applyFromArray($styleArray);
        $sheet->getStyle("A{$keteranganRow2}:J{$keteranganRow2}")->applyFromArray($styleArray);
        
        // Set tinggi baris untuk keterangan
        $sheet->getRowDimension($keteranganRow1)->setRowHeight(20);
        $sheet->getRowDimension($keteranganRow2)->setRowHeight(20);
    }

    protected function terbilang($angka): string
    {
        $f = new \NumberFormatter("id", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($angka)) . ' rupiah';
    }
}