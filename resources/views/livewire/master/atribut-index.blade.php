@php $isOwnerRole = Auth::user()->peran === 'OWNER'; @endphp

<div class="p-4 md:p-8 max-w-5xl mx-auto fade-in">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="font-headline text-2xl md:text-3xl font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Master Atribut (Spesifikasi Dinamis)</h2>
            <p class="text-slate-400 text-sm mt-1">Kelola atribut spesifikasi produk (Merk, Motif, dll).</p>
        </div>
        <button wire:click="$set('form_open', true)"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm text-white shadow-md hover:shadow-lg transition-all
                       {{ $isOwnerRole ? 'bg-gradient-to-r from-blue-pro to-blue-600' : 'bg-sage-dark hover:bg-sage' }}">
            <span class="material-symbols-outlined text-[18px]">add</span> Tambah Atribut
        </button>
    </div>

    @if(session()->has('sukses'))
        <div class="bg-emerald-50 text-emerald-700 p-3 mb-5 rounded-xl text-sm font-semibold flex items-center gap-2 border border-emerald-100">
            <span class="material-symbols-outlined text-[18px]">check_circle</span> {{ session('sukses') }}
        </div>
    @endif

    @if($form_open)
        <div class="bg-white rounded-2xl overflow-hidden mb-6 border {{ $isOwnerRole ? 'border-slate-200' : 'border-slate-200/70' }}">
            <div class="px-6 py-4 border-b flex justify-between items-center {{ $isOwnerRole ? 'bg-slate-50 border-slate-200' : 'bg-sage-light/50 border-sage/10' }}">
                <h3 class="font-headline text-lg font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">{{ $edit_id ? 'Edit Atribut' : 'Buat Atribut Baru' }}</h3>
                <button wire:click="resetForm" class="text-slate-400 hover:text-red-500 transition-colors"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Nama Atribut</label>
                        <input type="text" wire:model="nama_atribut" placeholder="Contoh: Merk, Motif, Ring..."
                               class="w-full border-0 rounded-lg p-2.5 text-sm bg-slate-50 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
                        @error('nama_atribut') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Pilihan Opsi (Minimal 1)</label>
                        <div class="flex gap-2 mb-2">
                            <input type="text" wire:model="opsi_baru" wire:keydown.enter="tambahOpsi" placeholder="Ketik lalu Enter"
                                   class="w-full border-0 rounded-lg p-2.5 text-sm bg-slate-50 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
                            <button type="button" wire:click="tambahOpsi" class="px-3 py-2 rounded-lg text-sm font-bold text-white {{ $isOwnerRole ? 'bg-charcoal' : 'bg-sage-dark' }} shrink-0">Tambah</button>
                        </div>
                        @error('pilihan_opsi') <span class="text-red-500 text-xs font-semibold mb-2 block">{{ $message }}</span> @enderror
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            @foreach($pilihan_opsi as $index => $opsi)
                                <span class="bg-teal-50 text-teal-700 px-3 py-1 rounded-full text-sm font-semibold flex items-center gap-1.5">
                                    {{ $opsi }}
                                    <button type="button" wire:click="hapusOpsi({{ $index }})" class="text-red-400 hover:text-red-600 font-bold text-xs">×</button>
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex gap-2 mt-6">
                    <button wire:click="simpan" class="px-6 py-2.5 rounded-xl font-bold text-sm text-white shadow-md {{ $isOwnerRole ? 'bg-blue-pro hover:bg-blue-800' : 'bg-sage-dark hover:bg-sage' }} transition-colors">Simpan Atribut</button>
                    <button wire:click="resetForm" class="px-5 py-2.5 rounded-xl font-semibold text-sm text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl overflow-hidden border {{ $isOwnerRole ? 'border-slate-200' : 'border-slate-200/70' }}">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 {{ $isOwnerRole ? 'border-b border-slate-100' : 'border-b border-sage/10' }}">
                        <th class="p-4 w-1/3">Nama Atribut</th><th class="p-4">Pilihan (JSON Opsi)</th><th class="p-4 w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @foreach($daftarAtribut as $attr)
                    <tr class="{{ $isOwnerRole ? 'hover:bg-slate-50 border-b border-slate-50' : 'hover:bg-sage-light/20 border-b border-sage/5' }} transition-colors">
                        <td class="p-4 font-semibold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">{{ $attr->nama_atribut }}</td>
                        <td class="p-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($attr->pilihan_opsi as $o)
                                    <span class="bg-slate-100 text-slate-600 text-[10px] px-2 py-0.5 rounded-full font-semibold">{{ $o }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="p-4">
                            <button wire:click="edit({{ $attr->id_atribut }})" class="text-xs font-bold {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage' }} hover:underline">Edit</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>