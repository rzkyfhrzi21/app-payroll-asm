<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN STATUS TABEL & AJAX SINKRONISASI):
     - Tujuan Komponen: Tempat bersarangnya indikator loading (animasi berputar / skeleton),
       pesan error, dan pagination dinamis saat tabel diperbarui tanpa reload via AJAX.
     - Analogi Dunia Nyata: Seperti lampu indikator di timbangan truk batubara yang berkedip
       "Sedang Menimbang..." sebelum akhirnya angka berat muatan dan tombol cetak muncul.
     - Data dari Controller / Props:
       * $entity: Nama entitas kunci untuk AJAX endpoint (misal: 'karyawan', 'ritase', 'truk').
       * $entityLabel: Nama sebutan yang dibaca manusia (misal: 'data supir').
       * $colspan: Jumlah kolom tabel untuk menyelaraskan lebar kotak status.
     ============================================================================== -->
@props([
    'entityLabel' => 'data',
    'entity' => 'karyawan',
    'resetExpr' => null,
    'colspan' => 10,
])

{{-- Komponen Bersama: AJAX POST Sync, Loading Spinner, Skeleton Loading, Empty State, Error State, & Pagination Interaktif (BRIEF Bab 4 & Bab 5) --}}
<div
    class="tabel-status-dan-paginasi border-t border-card-border bg-surface/60"
    data-entity="{{ $entity }}"
    data-entity-label="{{ $entityLabel }}"
    data-colspan="{{ $colspan }}"
    @if ($resetExpr) data-reset-expr="{{ $resetExpr }}" @endif
></div>
