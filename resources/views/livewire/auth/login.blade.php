{{-- Halaman Login — modern minimalist, responsive (mobile-first) --}}
<div class="min-h-screen w-full flex flex-col">

    {{-- Bar atas: brand --}}
    <header class="flex items-center justify-between px-6 sm:px-10 pt-7 pb-4">
        <div class="flex items-center gap-2.5">
            <div class="h-9 w-9 rounded-xl bg-neutral-900 text-white grid place-items-center font-headline font-bold text-sm">
                S
            </div>
            <span class="font-headline text-lg font-bold tracking-tight text-neutral-900">
                Stokify<span class="font-light text-neutral-400">wsm</span>
            </span>
        </div>
        <span class="hidden sm:block text-[11px] font-label font-semibold uppercase tracking-[0.22em] text-neutral-400">
            Inventory &amp; Point of Sales
        </span>
    </header>

    {{-- Inti: headline + form --}}
    <main class="flex-1 flex items-center justify-center px-6 py-8 sm:py-12">
        <div class="w-full max-w-md">

            {{-- Headline --}}
            <div class="text-center mb-8 sm:mb-10">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-3 py-1 text-[11px] font-semibold text-neutral-500 shadow-sm">
                    <span class="material-symbols-outlined text-[14px] text-emerald-500" style="font-variation-settings:'FILL' 1;">verified</span>
                    Sistem Internal
                </span>
                <h1 class="mt-5 font-headline text-[2.4rem] leading-[1.05] sm:text-5xl font-medium tracking-tight text-neutral-900">
                    Selamat datang<br>kembali.
                </h1>
                <p class="mt-4 text-sm text-neutral-500">
                    Masuk untuk melanjutkan ke dasbor Anda.
                </p>
            </div>

            {{-- Kartu form --}}
            <div class="rounded-3xl border border-neutral-200/80 bg-white p-6 sm:p-8 shadow-[0_12px_45px_-15px_rgba(0,0,0,0.15)]">

                {{-- Pesan error login --}}
                @error('login')
                    <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <span class="material-symbols-outlined text-[18px] shrink-0">error</span>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                <form wire:submit="prosesLogin" class="space-y-4" x-data="{ show: false }">

                    {{-- Username --}}
                    <div>
                        <label for="username" class="mb-1.5 block text-sm font-medium text-neutral-700">Username</label>
                        <div class="relative">
                            <span class="material-symbols-outlined pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[20px] text-neutral-400">person</span>
                            <input type="text"
                                   id="username"
                                   wire:model="username"
                                   autofocus
                                   autocomplete="username"
                                   placeholder="Masukkan username"
                                   class="w-full rounded-xl border border-neutral-200 bg-neutral-50 py-3 pl-11 pr-4 text-base text-neutral-900 placeholder:text-neutral-400 transition focus:border-neutral-900 focus:bg-white focus:outline-none focus:ring-4 focus:ring-neutral-900/5">
                        </div>
                        @error('username') <span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-neutral-700">Kata Sandi</label>
                        <div class="relative">
                            <span class="material-symbols-outlined pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[20px] text-neutral-400">lock</span>
                            <input :type="show ? 'text' : 'password'"
                                   id="password"
                                   wire:model="password"
                                   autocomplete="current-password"
                                   placeholder="Masukkan kata sandi"
                                   class="w-full rounded-xl border border-neutral-200 bg-neutral-50 py-3 pl-11 pr-11 text-base text-neutral-900 placeholder:text-neutral-400 transition focus:border-neutral-900 focus:bg-white focus:outline-none focus:ring-4 focus:ring-neutral-900/5">
                            <button type="button"
                                    @click="show = !show"
                                    tabindex="-1"
                                    aria-label="Tampilkan kata sandi"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 grid h-8 w-8 place-items-center rounded-lg text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-600">
                                <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                            </button>
                        </div>
                        @error('password') <span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    {{-- Tombol masuk --}}
                    <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="prosesLogin"
                            class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-neutral-900 py-3.5 text-sm font-semibold text-white transition hover:bg-neutral-800 focus:outline-none focus:ring-4 focus:ring-neutral-900/20 disabled:cursor-not-allowed disabled:opacity-70">
                        <span wire:loading.remove wire:target="prosesLogin" class="flex items-center gap-2">
                            Masuk
                            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                        </span>
                        <span wire:loading wire:target="prosesLogin" class="flex items-center gap-2">
                            <span class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                            Memproses…
                        </span>
                    </button>
                </form>
            </div>

            {{-- Footer --}}
            <p class="mt-8 text-center text-xs text-neutral-400">
                Dibuat oleh <span class="font-semibold text-neutral-500">Roger Jeremy</span>
            </p>
        </div>
    </main>
</div>
