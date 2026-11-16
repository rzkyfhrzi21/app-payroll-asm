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
