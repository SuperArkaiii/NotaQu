<?php

namespace App\Exports;

use App\Models\NotaPenjualan;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class NotaGabungan2Export
{
    protected Collection $notas;

    public function __construct(Collection $notas)
    {
        $this->notas = $notas;
    }

    public function download(): StreamedResponse
    {
        $templatePath = storage_path('app/templates/contoh surat jalan.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $startRow1 = 14; // Baris awal untuk data barang tabel pertama
        $baseStartRow2 = 46; // Baris awal DASAR untuk data barang tabel kedua

        // Gabungkan semua items dari semua nota
        $allItems = collect();
        $totalKoli = 0;
        $firstNota = $this->notas->first();

        foreach ($this->notas as $nota) {
            $items = $nota->items;
            $allItems = $allItems->merge($items);
            $totalKoli += $items->count();
        }

        // Hitung berapa baris tambahan yang dibutuhkan
        $additionalRows = $allItems->count() > 9 ? $allItems->count() - 9 : 0;
        
        // Hitung startRow2 yang sebenarnya setelah insert
        $startRow2 = $baseStartRow2 + $additionalRows;

        // Insert additional rows untuk data barang jika lebih dari 9 item untuk kedua tabel
        $this->insertRowsForItems($sheet, $allItems->count(), $startRow1, 23); // Tabel pertama
        $this->insertRowsForItems($sheet, $allItems->count(), $startRow2, 55 + $additionalRows); // Tabel kedua (disesuaikan)

        // Format tanggal kirim dengan pengecekan tipe data
        $tanggalKirim = $this->formatTanggalKirim($firstNota->tanggal_kirim);

        // Header - sesuai dengan struktur Template Surat Jalan
        // Blok I sampai K (Tabel 1)
        $sheet->setCellValue("I7", $firstNota->kode_faktur . '/DO/RPN/05'); // Faktur
        $sheet->setCellValue("I8", $tanggalKirim); // Tanggal Kirim
        $sheet->setCellValue("I9", $firstNota->kode_faktur . '/PO/05'); // Nomor PO
        
        // Blok B sampai E (Tabel 1)
        $sheet->setCellValue("B7", $firstNota->dataPelanggan->nama ?? '-'); // Nama
        
        // Alamat diblok dari kolom 8 sampai 11 (Tabel 1)
        $alamat = $firstNota->dataPelanggan->alamat ?? '-';
        $sheet->setCellValue("B8", $alamat); // Alamat
        $sheet->mergeCells("B8:B11"); // Merge alamat dari baris 8-11

        // Header untuk Tabel 2 (duplikasi dari tabel 1) - DISESUAIKAN DENGAN PERGESERAN
        $headerRow2Base = 39 + $additionalRows; // Baris header tabel 2 setelah pergeseran
        $sheet->setCellValue("I{$headerRow2Base}", $firstNota->kode_faktur . '/2025/DO/RPN/05'); // Faktur
        $sheet->setCellValue("I" . ($headerRow2Base + 1), $tanggalKirim); // Tanggal Kirim
        $sheet->setCellValue("I" . ($headerRow2Base + 2), $firstNota->kode_faktur . '/PO/05'); // Nomor PO
        
        $sheet->setCellValue("B{$headerRow2Base}", $firstNota->dataPelanggan->nama ?? '-'); // Nama
        $sheet->setCellValue("B" . ($headerRow2Base + 1), $alamat); // Alamat
        $sheet->mergeCells("B" . ($headerRow2Base + 1) . ":B" . ($headerRow2Base + 4)); // Merge alamat dari baris 40-43

        // Fill data untuk Tabel 1
        $this->fillItemData($sheet, $allItems, $startRow1);
        
        // Fill data untuk Tabel 2
        $this->fillItemData($sheet, $allItems, $startRow2);

        // Style untuk kedua tabel barang
        $endRow1 = $startRow1 + $allItems->count() - 1;
        $endRow2 = $startRow2 + $allItems->count() - 1;
        
        $this->addItemTableStyling($sheet, $startRow1, $endRow1);
        $this->addItemTableStyling($sheet, $startRow2, $endRow2);

        $writer = new Xlsx($spreadsheet);
        $kode_faktur = str_replace(['/', '\\', ':'], '-', $firstNota->kode_faktur);
        $filename = "SuratJalan{$kode_faktur}.xlsx";
        
        return response()->streamDownload(function () use ($writer) {
            ob_clean();
            flush();
            $writer->save('php://output');
        }, $filename);
    }

    /**
     * Format tanggal kirim dengan pengecekan tipe data
     */
    protected function formatTanggalKirim($tanggalKirim)
    {
        if (empty($tanggalKirim)) {
            return '-';
        }

        // Jika sudah berupa Carbon/DateTime object
        if ($tanggalKirim instanceof \Carbon\Carbon || $tanggalKirim instanceof \DateTime) {
            return $tanggalKirim->format('d F Y');
        }

        // Jika berupa string, coba parse menggunakan Carbon
        try {
            return Carbon::parse($tanggalKirim)->format('d F Y');
        } catch (\Exception $e) {
            // Jika gagal parse, return string asli atau default
            return is_string($tanggalKirim) ? $tanggalKirim : '-';
        }
    }

    protected function fillItemData($sheet, $allItems, $startRow)
    {
        $row = $startRow;
        $no = 1;

        foreach ($allItems as $item) { 
            $sheet->setCellValue("B{$row}", $no++); // NO. (kolom B)
            $sheet->setCellValue("C{$row}", $item->quantity); // Jumlah barang (kolom C)
            
            // Menggunakan satuan dari database, fallback ke "brng" jika kosong
            $satuan = !empty($item->satuan) ? $item->satuan : 'brng';
            $sheet->setCellValue("D{$row}", $satuan); // Satuan dari database (kolom D)
            
            // Nama barang (kolom E sampai F diblok)
            $sheet->setCellValue("E{$row}", $item->product->nama_produk ?? '-'); // Nama barang
            $sheet->mergeCells("E{$row}:F{$row}"); // Merge E-F untuk nama barang
            
            // Tambahkan keterangan_produk di kolom G sampai K (dimerge)
            $keterangan = !empty($item->keterangan_produk) ? $item->keterangan_produk : '';
            $sheet->setCellValue("G{$row}", $keterangan); // Keterangan produk
            $sheet->mergeCells("G{$row}:K{$row}"); // Merge G-K untuk keterangan produk
            
            $row++;
        }
    }

    protected function addItemTableStyling($sheet, $startRow, $endRow)
    {
        $totalItems = $endRow - $startRow + 1;
        
        // Style untuk border bold pada baris pertama (atas dan samping kiri)
        $firstRowBorderStyle = [
            'borders' => [
                'top' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
                'left' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
                'right' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        // Style untuk border bold hanya pada kolom nomor (B) untuk baris ke-2 dst
        $numberColumnBorderStyle = [
            'borders' => [
                'left' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
                'right' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        // Style untuk border normal (thin) untuk kolom lainnya pada baris ke-2 dst
        $normalBorderStyle = [
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

        // Style untuk nama barang (E-F) dengan border kiri normal
        $namaBarangBorderStyle = [
            'borders' => [
                'left' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'right' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'top' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        // Apply styling untuk baris pertama (nomor 1) - border bold atas dan kiri untuk B-F, khusus untuk G-K
        $sheet->getStyle("B{$startRow}:F{$startRow}")->applyFromArray($firstRowBorderStyle);
        $sheet->getStyle("G{$startRow}:K{$startRow}")->applyFromArray([
            'borders' => [
                'left' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                ],
                'top' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                ],
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                ],
                'right' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Apply styling untuk baris ke-2 dst jika ada
        if ($endRow > $startRow) {
            // Border bold hanya untuk kolom nomor (B) pada baris ke-2 dst
            $sheet->getStyle("B" . ($startRow + 1) . ":B{$endRow}")->applyFromArray($numberColumnBorderStyle);
            
            // Border normal untuk kolom C dan D pada baris ke-2 dst
            $sheet->getStyle("C" . ($startRow + 1) . ":D{$endRow}")->applyFromArray($normalBorderStyle);
            
            // Border normal untuk kolom nama barang (E-F) pada baris ke-2 dst
            $sheet->getStyle("E" . ($startRow + 1) . ":F{$endRow}")->applyFromArray($namaBarangBorderStyle);
            
            // Border untuk kolom keterangan (G-K) pada baris ke-2 dst
            $sheet->getStyle("G{$startRow}:K{$endRow}")->applyFromArray([
                'borders' => [
                    'top' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                    ],
                    'bottom' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                    ],
                    'left' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                    ],
                    'right' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    ],
                ],
            ]);
        }

        // Border bawah bold pada baris ke-9 (baris terakhir template asli)
        $ninthRow = $startRow + 8; // Baris ke-9 (baris 22 jika startRow = 14)
        
        // Pastikan baris ke-9 ada dalam range data
        if ($ninthRow <= $endRow) {
            if ($totalItems <= 9) {
                // Jika 9 item atau kurang, apply border bawah bold untuk kolom B-F pada baris ke-9
                $sheet->getStyle("B{$ninthRow}:F{$ninthRow}")->applyFromArray([
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            } else {
                // Jika lebih dari 9 item, hapus border bawah di baris ke-9 untuk kolom B-F
                $sheet->getStyle("B{$ninthRow}:F{$ninthRow}")->applyFromArray([
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                        ],
                    ],
                ]);
                
                // PENTING: Hapus juga border bawah untuk kolom keterangan (G-K) di baris ke-9
                $sheet->getStyle("G{$ninthRow}:K{$ninthRow}")->applyFromArray([
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                        ],
                        'top' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                        ],
                        'left' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                        ],
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            }
        }

        // Handle kolom keterangan (G-K) berdasarkan jumlah item
        if ($totalItems < 9) {
            // Jika kurang dari 9 item, hapus border bawah untuk kolom keterangan pada baris terakhir
            $sheet->getStyle("G{$endRow}:K{$endRow}")->applyFromArray([
                'borders' => [
                    'bottom' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                    ],
                ],
            ]);
        } elseif ($totalItems == 9) {
            // Jika tepat 9 item, beri border bawah tebal untuk kolom keterangan pada baris terakhir
            $sheet->getStyle("G{$endRow}:K{$endRow}")->applyFromArray([
                'borders' => [
                    'bottom' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '000000'],
                    ],
                    'right' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);
        } else {
            // Styling khusus untuk baris ke-10 dan seterusnya (untuk item > 9)
            for ($row = $ninthRow + 1; $row <= $endRow; $row++) {
                // Border untuk kolom B (nomor urut)
                $sheet->getStyle("B{$row}")->applyFromArray([
                    'borders' => [
                        'left' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                        'top' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                        'bottom' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
                
                // Border untuk kolom C dan D
                $sheet->getStyle("C{$row}:D{$row}")->applyFromArray([
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
                ]);
                
                // Border untuk kolom E-F (nama barang)
                $sheet->getStyle("E{$row}:F{$row}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
                
                // Merge dan style kolom keterangan (G-K) - TIDAK ADA BORDER BAWAH
                $sheet->mergeCells("G{$row}:K{$row}");
                $sheet->getStyle("G{$row}:K{$row}")->applyFromArray([
                    'borders' => [
                        'top' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                        ],
                        'bottom' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                        ],
                        'left' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                        ],
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            }
            
            // Untuk item yang melebihi 9, apply border bold di baris terakhir untuk kolom B-F dan G-K
            $sheet->getStyle("B{$endRow}:F{$endRow}")->applyFromArray([
                'borders' => [
                    'bottom' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);
            
            // Tambahkan border bawah tebal untuk kolom keterangan (G-K) di baris terakhir
            $sheet->getStyle("G{$endRow}:K{$endRow}")->applyFromArray([
                'borders' => [
                    'bottom' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '000000'],
                    ],
                    'right' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);
        }

        // Text alignment untuk kolom tertentu
        $sheet->getStyle("B{$startRow}:B{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // NO
        $sheet->getStyle("C{$startRow}:C{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Jumlah barang
        $sheet->getStyle("D{$startRow}:D{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Satuan
        $sheet->getStyle("E{$startRow}:F{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Nama barang
        $sheet->getStyle("G{$startRow}:K{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Keterangan produk
    }

    protected function insertRowsForItems($sheet, $totalItems, $startRow, $insertAfterRow)
    {
        // Template sudah menyediakan 9 baris kosong untuk data barang
        // Hanya insert rows jika ada lebih dari 9 item
        if ($totalItems > 9) {
            $additionalRows = $totalItems - 9;
            
            // Insert rows untuk kelebihan item
            // Untuk tabel pertama: insert setelah baris 23
            // Untuk tabel kedua: insert setelah baris 55 (atau sesuai parameter)
            for ($i = 0; $i < $additionalRows; $i++) {
                $sheet->insertNewRowBefore($insertAfterRow, 1);
            }
        }
    }
}