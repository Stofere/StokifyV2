<!DOCTYPE html>
<html>
<head>
    <title>Katalog Produk</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #2c3e50; font-size: 20px; }
        .header p { margin: 5px 0 0 0; color: #7f8c8d; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #bdc3c7; padding: 6px 8px; text-align: left; }
        th { background-color: #ecf0f1; color: #2c3e50; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .stok-habis { color: #e74c3c; font-weight: bold; }
        .stok-aman { color: #27ae60; font-weight: bold; }
        .meta { font-size: 9px; color: #7f8c8d; display: block; margin-top: 2px; }
        .group-header { background-color: #e8f4f8; font-weight: bold; color: #2980b9; text-transform: uppercase; font-size: 11px; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN KATALOG & STOK FISIK GUDANG</h1>
        <p>Filter: <strong>{{ $namaKategori }}</strong> | Tanggal Cetak: {{ $tanggal }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%" class="center">No</th>
                <th width="12%">Kode / SKU</th>
                <th width="40%">Nama Barang & Spesifikasi</th>
                <th width="13%" class="center">Stok</th>
                <th width="12%" class="center">Status</th>
                <th width="18%">Lokasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupedProduk as $namaKategori => $produks)
                <tr class="group-header">
                    <td colspan="6">KATEGORI: {{ $namaKategori }}</td>
                </tr>
                @php $nomor = 1; @endphp
                @foreach($produks as $prod)
                <tr>
                    <td class="center">{{ $nomor++ }}.</td>
                    <td style="font-family: monospace; font-size: 10px; text-transform: uppercase; font-weight: bold;">{{ strtoupper($prod->kode_barang) }}</td>
                    <td><strong>{{ $prod->nama_produk }}</strong></td>
                    <td class="center">
                        @if($prod->lacak_stok)
                            <span class="{{ $prod->stok_saat_ini <= 0 ? 'stok-habis' : 'stok-aman' }}">
                                {{ fmod($prod->stok_saat_ini, 1) == 0 ? (int)$prod->stok_saat_ini : $prod->stok_saat_ini }} {{ $prod->satuan }}
                            </span>
                        @else
                            Unlimited
                        @endif
                    </td>
                    <td class="center">
                        @if($prod->status_stok === 'AMAN')
                            <strong style="color: #27ae60;">AMAN</strong>
                        @elseif($prod->status_stok === 'MENIPIS')
                            <strong style="color: #e67e22;">MENIPIS</strong>
                        @elseif($prod->status_stok === 'HABIS')
                            <strong style="color: #e74c3c;">HABIS</strong>
                        @else
                            <strong>—</strong>
                        @endif
                    </td>
                    <td>{{ $prod->lokasi ?? '-' }}</td>
                </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>