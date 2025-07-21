<?php

namespace App\Exports;

use App\Models\NotaPenjualan;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // Header - sesuai dengan struktur Template Surat Jalan
        // Blok I sampai K (Tabel 1)
        $sheet->setCellValue("I7", $firstNota->kode_faktur . '/DO/RPN/05'); // Faktur
        $sheet->setCellValue("I8", $firstNota->tanggal->format('d F Y')); // Tanggal Kirim
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
        $sheet->setCellValue("I" . ($headerRow2Base + 1), $firstNota->tanggal->format('d F Y')); // Tanggal Kirim
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

    protected function fillItemData($sheet, $allItems, $startRow)
    {
        $row = $startRow;
        $no = 1;

        foreach ($allItems as $item) { 
            $sheet->setCellValue("B{$row}", $no++); // NO. (kolom B)
            $sheet->setCellValue("C{$row}", $item->quantity); // Jumlah barang (kolom C)
            $sheet->setCellValue("D{$row}", "brng"); // Satuan "brng" (kolom D)
            
            // Nama barang (kolom E sampai F diblok)
            $sheet->setCellValue("E{$row}", $item->product->nama_produk ?? '-'); // Nama barang
            $sheet->mergeCells("E{$row}:F{$row}"); // Merge E-F untuk nama barang
            
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

        // Style untuk border bold pada baris terakhir (bawah)
        $lastRowBorderStyle = [
            'borders' => [
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        // Apply styling untuk baris pertama (nomor 1) - border bold atas dan kiri
        $sheet->getStyle("B{$startRow}:F{$startRow}")->applyFromArray($firstRowBorderStyle);

        // Apply styling untuk baris ke-2 dst jika ada
        if ($endRow > $startRow) {
            // Border bold hanya untuk kolom nomor (B) pada baris ke-2 dst
            $sheet->getStyle("B" . ($startRow + 1) . ":B{$endRow}")->applyFromArray($numberColumnBorderStyle);
            
            // Border normal untuk kolom C dan D pada baris ke-2 dst
            $sheet->getStyle("C" . ($startRow + 1) . ":D{$endRow}")->applyFromArray($normalBorderStyle);
            
            // Border normal untuk kolom nama barang (E-F) pada baris ke-2 dst
            $sheet->getStyle("E" . ($startRow + 1) . ":F{$endRow}")->applyFromArray($namaBarangBorderStyle);
        }

        // Handle kolom keterangan (G-K) berdasarkan jumlah item
        if ($totalItems <= 9) {
            // Jika 9 item atau kurang, hapus border bawah untuk kolom data barang
            $sheet->getStyle("B{$endRow}:F{$endRow}")->applyFromArray([
                'borders' => [
                    'bottom' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                    ],
                ],
            ]);
        } else {
            // Jika lebih dari 9 item:
            $ninthRow = $startRow + 8; // Baris ke-9 (baris 22 jika startRow = 14)
            
            // PERBAIKAN: Untuk baris ke-9, hapus border bawah untuk semua kolom data barang
            $sheet->getStyle("B{$ninthRow}:F{$ninthRow}")->applyFromArray([
                'borders' => [
                    'bottom' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                    ],
                ],
            ]);
            
            // Perbaikan khusus untuk kolom B di baris ke-9 (nomor urut)
            $sheet->getStyle("B{$ninthRow}")->applyFromArray([
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
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                    ],
                ],
            ]);
            
            // Perbaikan khusus untuk kolom C dan D di baris ke-9
            $sheet->getStyle("C{$ninthRow}:D{$ninthRow}")->applyFromArray([
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
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                    ],
                ],
            ]);
            
            // Perbaikan khusus untuk kolom E-F (nama barang) di baris ke-9
            $sheet->getStyle("E{$ninthRow}:F{$ninthRow}")->applyFromArray([
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
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                    ],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);
            
            // Hapus border kiri, atas, dan bawah di baris ke-9 untuk kolom keterangan
            $sheet->getStyle("G{$ninthRow}:K{$ninthRow}")->applyFromArray([
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
                ],
            ]);
            
            // Untuk baris tambahan setelah baris ke-9
            for ($row = $ninthRow + 1; $row <= $endRow; $row++) {
                // Perbaikan khusus untuk kolom B (nomor urut) di baris tambahan
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
                
                // Perbaikan khusus untuk kolom C dan D di baris tambahan
                $sheet->getStyle("C{$row}:D{$row}")->applyFromArray([
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
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
                
                // Perbaikan khusus untuk kolom E-F (nama barang) di baris tambahan
                $sheet->getStyle("E{$row}:F{$row}")->applyFromArray([
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
                ]);
                
                // Merge kolom keterangan (G sampai K) untuk baris tambahan
                $sheet->mergeCells("G{$row}:K{$row}");
                
                // Apply style untuk kolom keterangan - HAPUS SEMUA BORDER KECUALI BORDER KANAN
                $additionalKeteranganStyle = [
                    'borders' => [
                        'left' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                        ],
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                        'top' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                        ],
                        'bottom' => ($row == $endRow) ? [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ] : [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ];
                
                // Apply style ke range yang di-merge (G sampai K)
                $sheet->getStyle("G{$row}:K{$row}")->applyFromArray($additionalKeteranganStyle);
            }
        }

        // PENTING: Apply border bold di baris terakhir SETELAH semua styling lainnya
        $sheet->getStyle("B{$endRow}:F{$endRow}")->applyFromArray($lastRowBorderStyle);

        // Text alignment untuk kolom tertentu
        $sheet->getStyle("B{$startRow}:B{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // NO
        $sheet->getStyle("C{$startRow}:C{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Jumlah barang
        $sheet->getStyle("D{$startRow}:D{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Satuan "brng"
        $sheet->getStyle("E{$startRow}:F{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Nama barang (E-F merged)
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