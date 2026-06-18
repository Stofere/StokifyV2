@php $isOwnerRole = Auth::user()->peran === 'OWNER'; @endphp

<div class="p-4 md:p-8 max-w-5xl mx-auto fade-in">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="font-headline text-2xl md:text-3xl font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Master Kategori & Relasi Atribut</h2>
            <p class="text-slate-400 text-sm mt-1">Atur kategori produk dan atribut spesifikasi yang terikat.</p>
        </div>
        <button wire:click="$set('form_open', true)"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm text-white shadow-md hover:shadow-lg transition-all
                       {{ $isOwnerRole ? 'bg-blue-pro hover:bg-blue-800' : 'bg-sage-dark hover:bg-sage' }}">
            <span class="material-symbols-outlined text-[18px]">add</span> Tambah Kategori
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
                <h3 class="font-headline text-lg font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">{{ $edit_id ? 'Edit Kategori' : 'Buat Kategori Baru' }}</h3>
                <button wire:click="resetForm" class="text-slate-400 hover:text-red-500 transition-colors"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-6">
                <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Nama Kategori</label>
                        <input type="text" wire:model="nama_kategori" placeholder="Contoh: Woofer, Fullring, Kabel..." class="w-full border-0 rounded-lg p-2.5 text-sm bg-slate-50 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
                        @error('nama_kategori') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Fitur Lacak Rol (Khusus Kabel)</label>
                        <label class="flex items-center gap-2 cursor-pointer mt-3">
                            <div class="relative">
                                <input type="checkbox" wire:model="lacak_rol" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all {{ $isOwnerRole ? 'peer-checked:bg-blue-pro' : 'peer-checked:bg-sage' }}"></div>
                            </div>
                            <span class="text-sm font-semibold text-slate-700">Aktifkan Tracking Rol Manual</span>
                        </label>
                    </div>
                </div>
                <div class="mb-5 p-4 rounded-xl {{ $isOwnerRole ? 'bg-blue-50/50 border border-blue-100' : 'bg-sage-light/40 border border-sage/10' }}">
                    <label class="block text-xs font-bold mb-2 {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage-dark' }}">Atribut yang aktif di kategori ini</label>
                    <p class="text-[10px] text-slate-400 mb-3">Centang atribut yang akan otomatis muncul saat admin menambah produk di kategori ini.</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach($daftarAtribut as $attr)
                            <label class="flex items-center gap-2 cursor-pointer bg-white p-2.5 rounded-lg hover:bg-slate-50 transition-colors">
                                <input type="checkbox" wire:model="selectedAtribut" value="{{ $attr->id_atribut }}" class="w-4 h-4 rounded {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage' }}">
                                <span class="text-sm font-semibold text-slate-700">{{ $attr->nama_atribut }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex gap-2">
                    <button wire:click="simpan" class="px-6 py-2.5 rounded-xl font-bold text-sm text-white shadow-md {{ $isOwnerRole ? 'bg-blue-pro hover:bg-blue-800' : 'bg-sage-dark hover:bg-sage' }} transition-colors">Simpan Kategori</button>
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
                        <th class="p-4 w-1/4">Kategori</th><th class="p-4">Atribut Terikat</th><th class="p-4 w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @foreach($daftarKategori as $kat)
                    <tr class="{{ $isOwnerRole ? 'hover:bg-slate-50 border-b border-slate-50' : 'hover:bg-sage-light/20 border-b border-sage/5' }} transition-colors">
                        <td class="p-4 font-semibold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">
                            {{ $kat->nama_kategori }}
                            @if($kat->lacak_rol)
                                <span class="ml-2 bg-indigo-100 text-indigo-700 text-[9px] px-1.5 py-0.5 rounded font-black tracking-widest uppercase">Lacak Rol</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($kat->atribut as $a)
                                    <span class="bg-teal-50 text-teal-700 text-[10px] px-2 py-0.5 rounded-full font-semibold">{{ $a->nama_atribut }}</span>
                                @empty
                                    <span class="text-slate-400 text-xs italic">Tidak ada atribut khusus</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="p-4">
                            <button wire:click="edit({{ $kat->id_kategori }})" class="text-xs font-bold {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage' }} hover:underline">Edit</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>