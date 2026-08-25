<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Collection;

class PenjualanExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $dataTransaksi;
    protected string $judulPeriode;
    protected string $tanggalCetak;
    protected bool $tampilkanUang;

    public function __construct($dataTransaksi, string $judulPeriode, string $tanggalCetak, bool $tampilkanUang = true)
    {
        $this->dataTransaksi = $dataTransaksi;
        $this->judulPeriode = $judulPeriode;
        $this->tanggalCetak = $tanggalCetak;
        $this->tampilkanUang = $tampilkanUang;
    }

    /**
     * Definisi kolom [judul, lebar] sebagai satu sumber kebenaran.
     * Huruf kolom untuk styles() diturunkan dari jumlahnya, bukan di-hardcode —
     * kalau tidak, perubahan jumlah kolom akan salah format tanpa error.
     * Peran non-OWNER tidak mendapat kolom rupiah sama sekali.
     */
    private function kolom(): array
    {
        $kolom = [
            ['No', 5], ['No. Item', 7], ['Waktu', 18], ['Nota', 18],
            ['Pelanggan', 18], ['Sales', 15], ['Nama Barang', 30], ['SKU', 15], ['Qty', 12],
        ];

        if ($this->tampilkanUang) {
            $kolom[] = ['Harga Satuan', 15];
            $kolom[] = ['Subtotal', 15];
            $kolom[] = ['Total Transaksi', 18];
        }

        return $kolom;
    }

    /** Huruf kolom terakhir, mis. 'L' untuk 12 kolom. */
    private function kolomTerakhir(): string
    {
        return Coordinate::stringFromColumnIndex(count($this->kolom()));
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

                $baris = [
                    $isFirst ? $nomor : '',
                    $itemNo++,
                    $isFirst ? \Carbon\Carbon::parse($trx->tanggal_transaksi)->format('d/m/Y H:i') : '',
                    $isFirst ? $trx->kode_nota : '',
                    $isFirst ? ($trx->pelanggan->nama ?? 'Umum') : '',
                    $isFirst ? ($trx->marketing->nama ?? '-') : '',
                    $det->produk->nama_produk,
                    $det->produk->kode_barang,
                    $qty . ' ' . strtoupper($det->satuan_saat_jual),
                ];

                if ($this->tampilkanUang) {
                    $baris[] = $det->harga_satuan;
                    $baris[] = $det->subtotal;
                    $baris[] = $isFirst ? $trx->total_harga : '';
                }

                $rows->push($baris);

                $isFirst = false;
            }

            $nomor++;
        }

        // Grand total hanya untuk peran yang boleh melihat uang
        if ($this->tampilkanUang) {
            $jml = count($this->kolom());
            $baris = array_fill(0, $jml, '');
            $baris[$jml - 2] = 'TOTAL OMZET:';
            $baris[$jml - 1] = $grandTotal;
            $rows->push($baris);
        }

        return $rows;
    }

    public function headings(): array
    {
        return array_column($this->kolom(), 0);
    }

    public function columnWidths(): array
    {
        $lebar = [];
        foreach ($this->kolom() as $i => [$judul, $w]) {
            $lebar[Coordinate::stringFromColumnIndex($i + 1)] = $w;
        }

        return $lebar;
    }

    public function styles(Worksheet $sheet): array
    {
        // Title row
        $akhir = $this->kolomTerakhir();

        $sheet->mergeCells("A1:{$akhir}1");
        $sheet->setCellValue('A1', "LAPORAN PENJUALAN — Periode: {$this->judulPeriode} | Cetak: {$this->tanggalCetak}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        $sheet->insertNewRowBefore(2, 1);

        // Format kolom currency
        $highestRow = $sheet->getHighestRow();
        if ($this->tampilkanUang) {
            // Tiga kolom rupiah selalu di paling kanan
            $awalUang = Coordinate::stringFromColumnIndex(count($this->kolom()) - 2);
            $sheet->getStyle("{$awalUang}3:{$akhir}{$highestRow}")->getNumberFormat()->setFormatCode('#,##0');
        }

        // Grand total row styling
        $sheet->getStyle("A{$highestRow}:{$akhir}{$highestRow}")->applyFromArray([
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
