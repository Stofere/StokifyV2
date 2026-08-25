<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class PenjualanExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $dataTransaksi;
    protected string $judulPeriode;
    protected string $tanggalCetak;

    public function __construct($dataTransaksi, string $judulPeriode, string $tanggalCetak)
    {
        $this->dataTransaksi = $dataTransaksi;
        $this->judulPeriode = $judulPeriode;
        $this->tanggalCetak = $tanggalCetak;
    }

    public function collection(): Collection
    {
        $rows = collect();
        $nomor = 1;
        $grandTotal = 0;

        foreach ($this->dataTransaksi as $trx) {
            $isFirst = true;
            $itemNo = 1;
            $grandTotal += $trx->total_harga;

            foreach ($trx->detailPenjualan as $det) {
                $qty = fmod($det->jumlah, 1) == 0 ? (int)$det->jumlah : $det->jumlah;

                $rows->push([
                    $isFirst ? $nomor : '',
                    $itemNo++,
                    $isFirst ? \Carbon\Carbon::parse($trx->tanggal_transaksi)->format('d/m/Y H:i') : '',
                    $isFirst ? $trx->kode_nota : '',
                    $isFirst ? ($trx->pelanggan->nama ?? 'Umum') : '',
                    $isFirst ? ($trx->marketing->nama ?? '-') : '',
                    $det->produk->nama_produk,
                    $det->produk->kode_barang,
                    $qty . ' ' . strtoupper($det->satuan_saat_jual),
                    $det->harga_satuan,
                    $det->subtotal,
                    $isFirst ? $trx->total_harga : '',
                ]);

                $isFirst = false;
            }

            $nomor++;
        }

        // Grand total row
        $rows->push(['', '', '', '', '', '', '', '', '', '', 'TOTAL OMZET:', $grandTotal]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'No', 'No. Item', 'Waktu', 'Nota', 'Pelanggan', 'Sales',
            'Nama Barang', 'SKU', 'Qty', 'Harga Satuan', 'Subtotal', 'Total Transaksi',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5, 'B' => 7, 'C' => 18, 'D' => 18, 'E' => 18, 'F' => 15,
            'G' => 30, 'H' => 15, 'I' => 12, 'J' => 15, 'K' => 15, 'L' => 18,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Title row
        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', "LAPORAN PENJUALAN — Periode: {$this->judulPeriode} | Cetak: {$this->tanggalCetak}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        $sheet->insertNewRowBefore(2, 1);

        // Format kolom currency
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle("J3:L{$highestRow}")->getNumberFormat()->setFormatCode('#,##0');

        // Grand total row styling
        $sheet->getStyle("A{$highestRow}:L{$highestRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'borders' => ['top' => ['borderStyle' => 'double']],
        ]);

        return [
            2 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2C3E50']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Laporan Penjualan';
    }
}
