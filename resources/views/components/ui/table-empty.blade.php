<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN TABEL KOSONG / EMPTY STATE):
     - Tujuan Komponen: Menampilkan pesan ramah di dalam baris tabel saat data nihil / kosong,
       baik karena belum ada data sama sekali atau karena hasil filter pencarian tidak cocok.
     - Analogi Dunia Nyata: Seperti laci berkas yang saat dibuka ternyata kosong; kita beri
       catatan pengingat: "Belum ada berkas, silakan tambah baru atau bersihkan saringan pencarian".
     - Data dari Controller / Props:
       * $colspan: Lebar bentangan kolom tabel agar pesan berada tepat di tengah (default: 8).
       * $label: Nama entitas yang sedang dicari (misal: "supir", "surat jalan", "kasbon").
       * $tampilkanReset: Menampilkan tombol untuk membersihkan kata kunci filter.
     ============================================================================== -->
@props([
    'colspan' => 8,
    'label' => 'data',
    'tampilkanReset' => true,
])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-12 text-center">
        <div class="mx-auto flex max-w-md flex-col items-center justify-center gap-2">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-surface-container text-slate-text-muted">
                <span class="material-symbols-outlined text-[1.75rem]">manage_search</span>
            </div>
            <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Data Tidak Ditemukan</h3>
            <p class="font-sans text-body-sm text-slate-text-secondary">
                Tidak ada {{ $label }} yang cocok dengan kata kunci pencarian atau kombinasi filter saat ini.
            </p>
            @if ($tampilkanReset)
                <a
                    href="{{ url()->current() }}"
                    onclick="if (window.resetTabelAjax) { window.resetTabelAjax(); return false; }"
                    class="mt-1 inline-flex items-center gap-1.5 rounded-lg bg-primary/10 px-3.5 py-2 font-sans text-label-md font-semibold text-primary transition-colors hover:bg-primary hover:text-on-primary"
                >
                    <span class="material-symbols-outlined text-[1rem]">filter_alt_off</span>
                    Reset Pencarian &amp; Filter
                </a>
            @endif
        </div>
    </td>
</tr>
