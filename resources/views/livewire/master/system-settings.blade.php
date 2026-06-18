@php $isOwnerRole = Auth::user()->peran === 'OWNER'; @endphp

<div class="p-4 md:p-8 max-w-4xl mx-auto fade-in">

    {{-- Header --}}
    <div class="mb-6">
        <h2 class="font-headline text-2xl md:text-3xl font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Pengaturan Sistem</h2>
        <p class="text-slate-400 text-sm mt-1">Konfigurasi tampilan dan preferensi sistem POS.</p>
    </div>

    {{-- Alert --}}
    @if(session()->has('sukses'))
        <div class="bg-emerald-50 text-emerald-700 p-3.5 mb-5 rounded-xl text-sm font-semibold flex items-center gap-2 border border-emerald-100">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            {{ session('sukses') }}
        </div>
    @endif

    {{-- Card: Background POS --}}
    <div class="bg-white rounded-2xl overflow-hidden mb-6 border {{ $isOwnerRole ? 'border-slate-200' : 'border-slate-200/70' }}">
        <div class="px-6 py-4 border-b flex items-center gap-3 {{ $isOwnerRole ? 'bg-slate-50 border-slate-200' : 'bg-sage-light/50 border-sage/10' }}">
            <span class="material-symbols-outlined text-[22px] {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage' }}">wallpaper</span>
            <h3 class="font-headline text-lg font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Background Halaman POS</h3>
        </div>

        <div class="p-6">
            <p class="text-sm text-slate-500 mb-4">Upload gambar untuk dijadikan background halaman kasir POS. Jika tidak diatur, akan menggunakan tampilan default.</p>

            {{-- Preview --}}
            <div class="mb-5">
                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Preview Saat Ini</label>
                @if($pos_background_preview)
                    <div class="relative w-full h-56 rounded-xl overflow-hidden border-2 border-dashed {{ $isOwnerRole ? 'border-blue-pro/30' : 'border-sage/30' }} bg-[#f0f0f0]" style="background-image: repeating-conic-gradient(#e0e0e0 0% 25%, transparent 0% 50%); background-size: 16px 16px;">
                        <img src="{{ $pos_background_preview }}" alt="POS Background" class="w-full h-full object-contain">
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/40 to-transparent flex items-end p-3">
                            <span class="text-white text-xs font-bold bg-black/30 px-2 py-1 rounded-lg backdrop-blur-sm">Custom Background</span>
                        </div>
                    </div>
                @else
                    <div class="w-full h-48 rounded-xl border-2 border-dashed {{ $isOwnerRole ? 'border-slate-200' : 'border-sage/20' }} flex items-center justify-center bg-slate-50">
                        <div class="text-center">
                            <span class="material-symbols-outlined text-4xl text-slate-300 mb-2 block">image</span>
                            <p class="text-sm text-slate-400 font-semibold">Default (Tanpa Background)</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Upload + Preview (Alpine.js FileReader: tidak bergantung endpoint server) --}}
            <div x-data="{ localPreview: null, hasFile: false }"
                 x-on:change.capture="
                    const f = $event.target.files?.[0];
                    if (f) {
                        hasFile = true;
                        const reader = new FileReader();
                        reader.onload = e => localPreview = e.target.result;
                        reader.readAsDataURL(f);
                    } else {
                        hasFile = false;
                        localPreview = null;
                    }
                 "
                 class="flex flex-col gap-4">

                {{-- Input file --}}
                <div>
                    <input type="file" wire:model="pos_background_image" accept="image/*"
                           class="w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold {{ $isOwnerRole ? 'file:bg-blue-50 file:text-blue-pro' : 'file:bg-sage-light file:text-sage-dark' }} hover:file:opacity-80 cursor-pointer">
                    @error('pos_background_image') <span class="text-red-500 text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                </div>

                {{-- Preview Gambar Baru (dari FileReader, tidak lewat server) --}}
                <div x-show="localPreview" x-cloak class="p-2 bg-amber-50 border border-amber-200 rounded-xl">
                    <p class="text-[10px] text-amber-700 font-bold uppercase mb-2 tracking-wider">Preview Gambar Baru (Sebelum Disimpan):</p>
                    <div class="rounded-lg overflow-hidden bg-slate-100" style="background-image: repeating-conic-gradient(#e0e0e0 0% 25%, transparent 0% 50%); background-size: 16px 16px;">
                        <img :src="localPreview" alt="Preview" class="w-full h-48 object-contain">
                    </div>
                </div>

                {{-- Wire loading indicator --}}
                <div wire:loading wire:target="pos_background_image" class="text-xs text-slate-500 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] animate-spin">refresh</span> Mengupload ke server...
                </div>

                {{-- Tombol aksi --}}
                <div class="flex gap-2">
                    <button wire:click="simpanBackground"
                            x-bind:disabled="!hasFile"
                            class="px-5 py-2.5 rounded-xl font-bold text-sm text-white shadow-md transition-all disabled:opacity-40 disabled:cursor-not-allowed {{ $isOwnerRole ? 'bg-blue-pro hover:bg-blue-800' : 'bg-sage-dark hover:bg-sage' }}">
                        <span class="material-symbols-outlined text-[16px] align-middle mr-1">save</span> Simpan Background
                    </button>
                    @if($pos_background_preview)
                        <button wire:click="hapusBackground"
                                class="px-5 py-2.5 rounded-xl font-bold text-sm bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 transition-colors">
                            <span class="material-symbols-outlined text-[16px] align-middle mr-1">delete</span> Hapus
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
