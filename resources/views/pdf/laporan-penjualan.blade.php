<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #2c3e50; font-size: 20px; text-transform: uppercase; }
        .header p { margin: 5px 0 0 0; color: #7f8c8d; font-size: 11px; }
        
        .transaksi-box { margin-bottom: 15px; border: 1px solid #bdc3c7; border-radius: 4px; page-break-inside: avoid; }
        .trx-header { background-color: #ecf0f1; padding: 6px 8px; border-bottom: 1px solid #bdc3c7; }
        .trx-title { font-weight: bold; font-size: 11px; color: #2c3e50; margin: 0; }
        .trx-nota { font-size: 9px; color: #95a5a6; font-family: monospace; display: inline-block; margin-left: 10px; font-weight: normal;}
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px 8px; border-bottom: 1px solid #eee; vertical-align: top; }
        th { text-align: left; font-size: 9px; color: #7f8c8d; text-transform: uppercase; background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .total-row td { border-top: 2px solid #bdc3c7; font-weight: bold; font-size: 11px; background: #fff; }
        .grand-total { text-align: right; font-size: 14px; margin-top: 20px; padding-top: 10px; border-top: 3px double #2c3e50; }

        .retur-box { background-color: #fff4e6; border: 1px dashed #f39c12; margin-top: 4px; padding: 4px; font-size: 9px; border-radius: 2px; }
        .retur-title { font-weight: bold; color: #d35400; text-transform: uppercase; font-size: 8px; margin-bottom: 2px; display: block;}
        .retur-note { font-style: italic; color: #7f8c8d; margin-top: 2px; display: block; border-top: 1px solid #fae5d3; padding-top: 2px; }
        .badge-retur { background: #f39c12; color: #fff; padding: 2px 4px; border-radius: 2px; font-size: 8px; text-transform: uppercase; font-weight: bold; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Rincian Penjualan</h1>
        <p>Periode Laporan: <strong>{{ $judulPeriode }}</strong> | Dicetak Pada: {{ $tanggalCetak }}</p>
    </div>

    @php 
        $grandTotalOmset = 0; 
        $nomorUrut = 1;
    @endphp

    @forelse($dataTransaksi as $trx)
        @php $grandTotalOmset += $trx->total_harga; @endphp
        <div class="transaksi-box">
            <div class="trx-header">
                <p class="trx-title">
                    {{ $nomorUrut++ }}. &nbsp; {{ \Carbon\Carbon::parse($trx->tanggal_transaksi)->format('d/m/Y H:i') }} &nbsp;|&nbsp; 
                    Pelanggan: {{ $trx->pelanggan->nama ?? 'Umum' }} &nbsp;|&nbsp; 
                    Sales: {{ $trx->marketing->nama ?? '-' }}
                    @if($trx->status_penjualan === 'DIRETUR')
                        <span class="badge-retur">Ada Retur</span>
                    @endif
                </p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th width="50%">Nama Barang & SKU</th>
                        <th width="15%" class="text-center">Qty</th>
                        <th width="15%" class="text-right">Harga Satuan</th>
                        <th width="20%" class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($trx->detailPenjualan as $det)
                    <tr>
                        <td>
                            <strong>{{ $det->produk->nama_produk }}</strong><br>
                            <span style="font-size: 8px; color:#7f8c8d; font-family: monospace;">{{ $det->produk->kode_barang }}</span>
                            
                            {{-- LOGIKA MULTI-RETUR CETAK PDF --}}
                            @if($det->jumlah_diretur > 0)
                                @php
                                    $daftarJejakRetur = [];
                                    foreach($trx->transaksiRetur as $retur) {
                                        foreach($retur->detailRetur as $dRet) {
                                            if($dRet->id_produk_dikembalikan === $det->id_produk) {
                                                $daftarJejakRetur[] = ['detail' => $dRet, 'nota_retur' => $retur];
                                            }
                                        }
                                    }
                                @endphp
                                @foreach($daftarJejakRetur as $jejak)
                                    <div class="retur-box">
                                        <span class="retur-title">Diretur pada: {{ $jejak['nota_retur']->tanggal_retur->format('d/m/Y') }}</span>
                                        Kembali: <strong>{{ fmod($jejak['detail']->jumlah, 1) == 0 ? (int)$jejak['detail']->jumlah : $jejak['detail']->jumlah }} {{ strtoupper($det->satuan_saat_jual) }}</strong> ({{ $jejak['detail']->kondisi_barang_dikembalikan }})<br>
                                        Ganti dgn: <strong>{{ $jejak['detail']->produkPengganti->nama_produk }}</strong> ({{ fmod($jejak['detail']->jumlah, 1) == 0 ? (int)$jejak['detail']->jumlah : $jejak['detail']->jumlah }} {{ strtoupper($det->satuan_saat_jual) }})
                                        <span class="retur-note">"{{ $jejak['nota_retur']->catatan ?? 'Tanpa catatan' }}"</span>
                                    </div>
                                @endforeach
                            @endif
                        </td>
                        <td class="text-center">
                            {{ fmod($det->jumlah, 1) == 0 ? (int)$det->jumlah : $det->jumlah }} {{ strtoupper($det->satuan_saat_jual) }}
                            @if(strtolower($det->satuan_saat_jual) === 'meter' && $det->jumlah_potong_gudang)
                                <br><span style="font-size: 8px; color: #d35400;">⚖ {{ $det->jumlah_potong_gudang }} KG</span>
                            @endif
                        </td>
                        <td class="text-right">Rp {{ number_format($det->harga_satuan, 0, ',', '.') }}<br><span style="font-size: 8px; color: #7f8c8d;">/{{ $det->satuan_saat_jual }}</span></td>
                        <td class="text-right">Rp {{ number_format($det->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="3" class="text-right">TOTAL TRANSAKSI INI:</td>
                        <td class="text-right">Rp {{ number_format($trx->total_harga, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @empty
        <div style="text-align: center; padding: 50px; color: #7f8c8d;">
            <h3>Tidak ada transaksi yang tercatat pada periode ini.</h3>
        </div>
    @endforelse

    @if(count($dataTransaksi) > 0)
    <div class="grand-total">
        TOTAL OMZET KOTOR (PERIODE INI): <strong style="color: #27ae60;">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</strong>
    </div>
    @endif
</body>
</html>