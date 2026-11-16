@php
    $daftarToastAwal = [];

    if (session()->has('toast') && is_array(session('toast'))) {
        $muatanSesi = session('toast');
        unset($muatanSesi['action']);
        $daftarToastAwal[] = $muatanSesi;
    } else {
        if (session('status')) {
            $daftarToastAwal[] = [
                'success' => true,
                'type' => 'success',
                'title' => 'Operasi Berhasil',
                'message' => (string) session('status'),
                'timestamp' => now()->toIso8601String(),
            ];
        }

        if (session('galat')) {
            $daftarToastAwal[] = [
                'success' => false,
                'type' => 'error',
                'title' => 'Operasi Gagal Diproses',
                'message' => (string) session('galat'),
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }

    if (isset($errors) && $errors->any() && ! collect($daftarToastAwal)->contains(fn ($item) => ($item['type'] ?? '') === 'error')) {
        $semuaPesanGalat = $errors->all();
        $daftarToastAwal[] = [
            'success' => false,
            'type' => 'error',
            'title' => 'Validasi Formulir Gagal',
            'message' => implode(' ', array_slice($semuaPesanGalat, 0, 2)),
            'errors' => $errors->toArray(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
@endphp

<div
    x-data="toastStackManager(@js($daftarToastAwal))"
    @notify-toast.window="tambahToast($event.detail)"
    class="pointer-events-none fixed right-4 top-4 z-[95] flex w-full max-w-md flex-col gap-3"
    aria-live="polite"
>
    <template x-for="item in antrean" :key="item.id">
        <div
            x-show="item.terlihat"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-6 scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0 scale-100"
            x-transition:leave-end="opacity-0 translate-x-6 scale-95"
            @mouseenter="jedaTimer(item.id)"
            @mouseleave="lanjutTimer(item.id)"
            class="pointer-events-auto relative overflow-hidden rounded-xl border bg-white shadow-2xl"
            :class="item.type === 'error'
                ? 'border-rose-300 ring-1 ring-rose-500/20'
                : 'border-emerald-200 ring-1 ring-emerald-500/20'"
            role="alert"
        >
            <div class="flex items-start gap-3.5 p-4">
                {{-- Ikon Status --}}
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                    :class="item.type === 'error'
                        ? 'bg-rose-100 text-rose-600'
                        : 'bg-emerald-100 text-emerald-600'"
                >
                    <span
                        class="material-symbols-outlined text-[22px]"
                        x-text="item.type === 'error' ? 'error' : 'check_circle'"
                    ></span>
                </div>

                {{-- Konten Detail Toast --}}
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h4
                            class="font-display text-sm font-bold leading-snug"
                            :class="item.type === 'error' ? 'text-rose-900' : 'text-slate-900'"
                            x-text="item.title"
                        ></h4>
                    </div>

                    <p
                        class="mt-1 font-sans text-xs leading-relaxed"
                        :class="item.type === 'error' ? 'text-rose-800' : 'text-slate-600'"
                        x-text="item.message"
                    ></p>

                    {{-- Rincian Field Error Validasi (Khusus Error Toast) --}}
                    <template x-if="item.type === 'error' && item.details && item.details.length > 1">
                        <ul class="mt-2 list-disc space-y-0.5 pl-4 font-sans text-[11px] font-medium text-rose-700">
                            <template x-for="(pesanErr, idxErr) in item.details" :key="idxErr">
                                <li x-text="pesanErr"></li>
                            </template>
                        </ul>
                    </template>

                    <div class="mt-1.5 flex items-center justify-end text-[10px] font-medium text-slate-400">
                        <span x-show="item.type === 'error'" class="font-semibold text-rose-600">Klik &times; untuk menutup</span>
                        <span x-show="item.type !== 'error'" class="text-emerald-600">Menutup otomatis (2 detik)</span>
                    </div>
                </div>

                {{-- Tombol Close Manual (×) --}}
                <button
                    type="button"
                    @click="tutupToast(item.id)"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors"
                    :class="item.type === 'error'
                        ? 'bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white font-bold'
                        : 'text-slate-400 hover:bg-slate-100 hover:text-slate-700'"
                    aria-label="Tutup Notifikasi"
                >
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            {{-- Progress Slider Bar Countdown 2 Detik (Khusus Toast Sukses - BRIEF Bab 1.3) --}}
            <template x-if="item.type !== 'error'">
                <div class="h-1 w-full bg-emerald-100">
                    <div
                        class="h-full bg-emerald-500 transition-all duration-75 ease-linear"
                        :style="'width: ' + item.progressPersen + '%;'"
                    ></div>
                </div>
            </template>
            <template x-if="item.type === 'error'">
                <div class="h-1 w-full bg-rose-500"></div>
            </template>
        </div>
    </template>
</div>
