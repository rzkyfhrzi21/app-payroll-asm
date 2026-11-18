<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN MASTER TARIF RUTE & JALUR ANGKUTAN):
     - Tujuan Halaman: Menetapkan koridor trayek pengiriman batubara dari mulut tambang
       (pit/stockpile asal) hingga pelabuhan/dermaga (jetty/pabrik tujuan).
     - Analogi Dunia Nyata: Seperti daftar ongkos trayek angkutan: berapa kilometer jarak
       tempuh jalan tambang, berapa upah angkut per tonase batubara (tarif_per_ton),
       dan berapa jatah uang jalan supir (uang saku solar & konsumsi) untuk setiap ritase.
     - Standar Aturan Master Rute (Sesuai AGENTS.md Bab 3.2 & 3.5):
       * Tarif dan uang jalan disimpan dalam nominal rupiah bulat (`BIGINT`).
       * Alamat asal dan alamat tujuan ditulis lengkap (textarea).
       * Rute yang sudah terikat dengan surat jalan (ritase) tidak boleh dihapus sembarangan.
     - Data dari Controller (Admin/Master/RouteController@index):
       * $summary: Total rute aktif, rata-rata tarif per ton, dan rata-rata jarak tempuh KM.
       * $routes: Paginator daftar rute dari tabel `rute`.
     ============================================================================== -->
@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');

    $barisDetailRute = "[
        { label: 'Kode Rute', nilai: ruteDipilih.code },
        { label: 'Material', nilai: ruteDipilih.name },
        { label: 'Alamat Asal', nilai: ruteDipilih.alamat_asal },
        { label: 'Alamat Tujuan', nilai: ruteDipilih.alamat_tujuan },
        { label: 'Jarak Tempuh', nilai: ruteDipilih.jarak_km !== null ? ruteDipilih.jarak_km + ' KM' : 'Belum diisi' },
        { label: 'Tarif per Ton', nilai: 'Rp ' + Number(ruteDipilih.rate || 0).toLocaleString('id-ID') },
        { label: 'Uang Jalan Default', nilai: 'Rp ' + Number(ruteDipilih.trip_money || 0).toLocaleString('id-ID') },
        { label: 'Status Rute', nilai: ruteDipilih.status === 'aktif' ? 'Aktif' : 'Nonaktif' },
        { label: 'Pengantaran Selesai', nilai: (ruteDipilih.ritase_count || 0) + ' pengantaran' },
        { label: 'Total Tonase Diangkut', nilai: (ruteDipilih.tonase || '0,00') + ' Ton' },
    ]";

    $adaFilterAktif = $filter['cari'] !== '' || $filter['status'] !== 'all' || $filter['material'] !== 'all';
@endphp

<x-app-layout title="Data Master — Tarif Rute & Jalur Angkutan">
    <div class="flex flex-col gap-6" x-data="masterPage()">
        <x-ui.page-header
            title="Data Master - Tarif Rute & Jalur Angkutan"
            breadcrumb="Data Master / Rute"
            subtitle="Koridor pengangkutan batubara: jarak tempuh, alamat asal-tujuan, tarif angkutan per ton, dan uang jalan default."
        >
            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-primary-container px-4 py-2 font-sans text-body-sm font-semibold text-on-primary shadow-md transition-all hover:bg-primary" @click="modalRuteTambah = true">
                <span class="material-symbols-outlined text-[1.125rem]">add_circle</span>
                Tambah Tarif Rute
            </button>
        </x-ui.page-header>

        {{-- Statistik rute --}}
        <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-tile
                label="Total Rute"
                :nilai="$summary['total']"
                satuan="rute"
                ikon="alt_route"
                tone="primary"
                :progres="$summary['total'] > 0 ? round($summary['aktif'] / $summary['total'] * 100) : 0"
                :progres-label="$summary['aktif'].' rute aktif'"
            />
            <x-ui.stat-tile
                label="Tarif Rata-rata"
                :nilai="$rp($summary['rata_tarif'])"
                satuan="/ ton"
                ikon="payments"
                tone="emerald"
                :catatan="'Tertinggi '.$rp($summary['tarif_tertinggi']).' • Terendah '.$rp($summary['tarif_terendah'])"
            />
            <x-ui.stat-tile
                label="Jarak Rata-rata"
                :nilai="number_format($summary['rata_jarak'], 1, ',', '.')"
                satuan="km"
                ikon="straighten"
                tone="amber"
                :catatan="$summary['material'].' jenis material terlayani'"
            />
            <x-ui.stat-tile
                label="Uang Jalan Default"
                :nilai="$rp($summary['total_uang_jalan'])"
                ikon="account_balance_wallet"
                tone="slate"
                :catatan="$summary['nonaktif'].' rute nonaktif'"
            />
        </div>

        {{-- Tabel rute --}}
        <div data-wadah-tabel="rute" class="flex flex-col overflow-hidden rounded-xl bg-surface-container-lowest shadow-card">
            <div class="flex flex-col gap-space-md border-b border-card-border p-space-md">
                <div>
                    <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Tabel Tarif Rute</h2>
                    <p class="font-sans text-body-sm text-slate-text-secondary">
                        <strong class="text-slate-text-primary">Pengantaran Selesai</strong> dan <strong class="text-slate-text-primary">Total Tonase</strong>
                        menunjukkan angkutan yang <em>sudah selesai diantarkan</em> melalui rute ini.
                    </p>
                </div>

                <div
                    class="flex flex-col items-center justify-between gap-space-sm md:flex-row"
                >
                    <div class="relative w-full md:w-80">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[1.125rem] text-slate-text-muted">search</span>
                        <input
                            type="text"
                            name="cari"
                            value="{{ $filter['cari'] }}"
                            class="w-full rounded-lg bg-surface py-2 pl-9 pr-4 font-sans text-body-sm text-slate-text-primary placeholder:text-slate-text-muted focus:outline-none focus:ring-2 focus:ring-primary-container"
                            placeholder="Cari kode rute, material, asal/tujuan..."
                        >
                    </div>

                    <div class="flex w-full flex-wrap items-center gap-space-sm md:w-auto">
                        <select name="material" class="rounded-lg bg-surface px-3 py-2 font-sans text-body-sm text-slate-text-secondary focus:outline-none focus:ring-2 focus:ring-primary-container">
                            <option value="all">Material: Semua</option>
                            @foreach ($materialOptions as $mat)
                                <option value="{{ $mat }}" @selected($filter['material'] === $mat)>Material: {{ $mat }}</option>
                            @endforeach
                        </select>

                        <select name="status" class="rounded-lg bg-surface px-3 py-2 font-sans text-body-sm text-slate-text-secondary focus:outline-none focus:ring-2 focus:ring-primary-container">
                            <option value="all">Status: Semua</option>
                            <option value="aktif" @selected($filter['status'] === 'aktif')>Status: Aktif</option>
                            <option value="nonaktif" @selected($filter['status'] === 'nonaktif')>Status: Nonaktif</option>
                        </select>

                        <button
                            type="button"
                            data-tombol-reset-filter
                            onclick="window.resetTabelAjax('rute')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 font-sans text-body-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100 {{ $adaFilterAktif ? '' : 'hidden' }}"
                        >
                            <span class="material-symbols-outlined text-[1rem]">restart_alt</span>
                            <span>Reset Filter</span>
                        </button>
                    </div>
                </div>
            </div>

            <x-ui.tabel-massal
                url="{{ route('admin.master.routes.status-massal') }}"
                :opsi-status="\App\Models\Rute::daftarStatus()"
                label="rute"
                keterangan="Pilih status rute…"
            >
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider">
                            <th class="w-10 px-2 py-3 text-center font-semibold text-slate-text-secondary">
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
                                    :checked="semuaTerpilih"
                                    @change="pilihSemuaBaris($event.target.checked)"
                                    aria-label="Pilih semua rute"
                                >
                            </th>
                            <th class="w-14 px-4 py-3 text-center font-semibold text-slate-text-secondary">No</th>
                            <th class="px-4 py-3 font-semibold text-slate-text-secondary">Kode Rute</th>
                            <th class="px-4 py-3 font-semibold text-slate-text-secondary">Material</th>
                            <th class="px-4 py-3 font-semibold text-slate-text-secondary">Asal &rarr; Tujuan</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-text-secondary">Jarak</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-text-secondary">Tarif / Ton</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-text-secondary">Uang Jalan</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-text-secondary">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-text-secondary">Pengantaran Selesai</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-text-secondary">Total Tonase Diangkut</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-text-secondary">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyRute" class="divide-y divide-card-border font-sans text-financial-table">
                        @foreach ($routes as $indeksBaris => $rute)
                            @include('components.tabel-baris.rute', ['baris' => $rute, 'nomor' => $routes->firstItem() + $indeksBaris])
                        @endforeach

                        @if ($routes->isEmpty())
                            <x-ui.table-empty :colspan="12" label="tarif rute" :tampilkan-reset="$adaFilterAktif" />
                        @endif
                    </tbody>
                </table>
            </div>
            </x-ui.tabel-massal>

            <x-ui.tabel-ajax entitas="rute" label="rute" target-id="tbodyRute" :kolom="12" :filter="$filter" :per-halaman-awal="$perPage" />
        </div>

        {{-- ============ MODAL TAMBAH TARIF RUTE ============ --}}
        <x-ui.modal-shell
            name="modalRuteTambah"
            title="Tambah Tarif Rute Angkutan"
            subtitle="Konfigurasi jalur pengantaran untuk perhitungan penggajian, jarak, tarif per ton, dan uang jalan standar."
            icon="alt_route"
            accent="gradient"
            badge="Formulir Baru"
            max-width="2xl"
        >
            <form method="POST" action="{{ route('admin.master.routes.store') }}" class="flex flex-col overflow-hidden">
                @csrf
                <div class="flex max-h-[65vh] flex-col gap-space-md overflow-y-auto px-space-lg pb-space-md">
                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Kode Rute" name="kode" :required="true" icon="tag"
                            hint="Dipakai sebagai kode pada surat jalan dan slip gaji." :error="$errors->first('kode')">
                            <input type="text" id="kode" name="kode" value="{{ old('kode') }}"
                                maxlength="20" placeholder="TAL-PORT" required @input="paksaHurufBesar($event)"
                                class="w-full rounded-lg bg-surface-container-low py-2 pl-9 pr-3 font-sans text-body-md font-semibold uppercase tracking-wide text-slate-text-primary transition-all placeholder:text-slate-text-muted focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Material Muatan" name="material" :required="true" icon="category"
                            hint="Pilihan standar: BATU BARA, OVERBURDEN, PASIR." :error="$errors->first('material')">
                            <input type="text" id="material" name="material" value="{{ old('material', 'BATU BARA') }}"
                                list="daftar-material" maxlength="50" placeholder="BATU BARA" required @input="paksaHurufBesar($event)"
                                class="w-full rounded-lg bg-surface-container-low py-2 pl-9 pr-3 font-sans text-body-md font-semibold uppercase tracking-wide text-slate-text-primary transition-all placeholder:text-slate-text-muted focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <datalist id="daftar-material">
                                <option value="BATU BARA"></option>
                                <option value="OVERBURDEN"></option>
                                <option value="PASIR"></option>
                            </datalist>
                        </x-ui.modal-field>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Alamat Asal" name="alamat_asal" icon="trip_origin" meta="(Opsional)"
                            :error="$errors->first('alamat_asal')">
                            <input type="text" id="alamat_asal" name="alamat_asal" value="{{ old('alamat_asal') }}"
                                maxlength="150" placeholder="Lokasi Pengantaran"
                                class="w-full rounded-lg bg-surface-container-low py-2 pl-9 pr-3 font-sans text-body-md text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Alamat Tujuan" name="alamat_tujuan" icon="place" meta="(Opsional)"
                            :error="$errors->first('alamat_tujuan')">
                            <input type="text" id="alamat_tujuan" name="alamat_tujuan" value="{{ old('alamat_tujuan') }}"
                                maxlength="150" placeholder="Lokasi Tujuan Pengantaran"
                                class="w-full rounded-lg bg-surface-container-low py-2 pl-9 pr-3 font-sans text-body-md text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-3">
                        <x-ui.modal-field label="Jarak Tempuh" name="jarak_km" icon="straighten" meta="(Opsional)"
                            :error="$errors->first('jarak_km')">
                            <input type="text" id="jarak_km" name="jarak_km" value="{{ old('jarak_km') }}"
                                inputmode="numeric" placeholder="120"
                                class="w-full rounded-lg bg-surface-container-low py-2 pl-9 pr-12 text-right font-financial-table text-body-md font-bold tabular-nums text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <span class="pointer-events-none absolute right-3 font-sans text-body-sm font-semibold text-slate-text-muted">KM</span>
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Tarif per Ton" name="tarif_per_ton" :required="true"
                            :error="$errors->first('tarif_per_ton')">
                            <span class="pointer-events-none absolute left-3 font-sans text-body-md font-semibold text-slate-text-muted">Rp</span>
                            <input type="text" id="tarif_per_ton" name="tarif_per_ton" value="{{ old('tarif_per_ton') }}"
                                inputmode="numeric" placeholder="158.760" required @input="formatRupiahInput($event)"
                                class="w-full rounded-lg bg-surface-container-low py-2 pl-10 pr-3 text-right font-financial-table text-body-md font-bold tabular-nums text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Uang Jalan Default" name="uang_jalan_default" :required="true"
                            :error="$errors->first('uang_jalan_default')">
                            <span class="pointer-events-none absolute left-3 font-sans text-body-md font-semibold text-slate-text-muted">Rp</span>
                            <input type="text" id="uang_jalan_default" name="uang_jalan_default" value="{{ old('uang_jalan_default') }}"
                                inputmode="numeric" placeholder="2.250.000" required @input="formatRupiahInput($event)"
                                class="w-full rounded-lg bg-surface-container-low py-2 pl-10 pr-3 text-right font-financial-table text-body-md font-bold tabular-nums text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>
                    </div>

                    <x-ui.modal-field label="Status Rute" name="status" :required="true" icon="toggle_on"
                        :error="$errors->first('status')">
                        <select id="status" name="status" required
                            class="w-full appearance-none rounded-lg bg-surface-container-low py-2 pl-9 pr-9 font-sans text-body-md text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="aktif" @selected(old('status', 'aktif') === 'aktif')>Aktif</option>
                            <option value="nonaktif" @selected(old('status') === 'nonaktif')>Nonaktif</option>
                        </select>
                        <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                    </x-ui.modal-field>
                </div>

                <div class="flex items-center justify-between gap-space-sm bg-canvas-bg px-space-lg py-space-md shadow-inner">
                    <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm transition-all hover:bg-surface-container-low" @click="modalRuteTambah = false">
                        Batal
                    </button>
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-primary-container px-space-xl py-2.5 font-sans text-body-md font-semibold text-on-primary shadow-md transition-all hover:bg-primary active:scale-95">
                        <span class="material-symbols-outlined text-[1.125rem]">check_circle</span>
                        Simpan Rute
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- ============ MODAL EDIT TARIF RUTE ============ --}}
        <x-ui.modal-shell
            name="modalRuteEdit"
            title="Ubah Tarif Rute Angkutan"
            subtitle="Perbarui koridor, jarak, tarif per ton, dan uang jalan default."
            icon="edit"
            max-width="2xl"
        >
            <form method="POST" :action="'{{ url('admin/master/routes') }}/' + ruteDipilih.id" class="flex flex-col overflow-hidden">
                @csrf
                @method('PATCH')
                <div class="flex max-h-[65vh] flex-col gap-space-md overflow-y-auto px-space-lg pb-space-md">
                    <div class="rounded-lg bg-surface-container-low px-space-md py-space-sm">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-muted">Kode Rute (tidak dapat diubah)</span>
                        <div class="font-financial-table text-body-md font-bold tracking-wide text-slate-text-primary" x-text="ruteDipilih.code"></div>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Material Muatan" name="edit_material" :required="true" icon="category"
                            :error="$errors->first('material')">
                            <input type="text" id="edit_material" name="material" x-model="ruteDipilih.name"
                                maxlength="50" required @input="paksaHurufBesar($event)"
                                class="w-full rounded-lg bg-surface-container-low py-2 pl-9 pr-3 font-sans text-body-md font-semibold uppercase tracking-wide text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Status Rute" name="edit_status" :required="true" icon="toggle_on"
                            :error="$errors->first('status')">
                            <select id="edit_status" name="status" x-model="ruteDipilih.status" required
                                class="w-full appearance-none rounded-lg bg-surface-container-low py-2 pl-9 pr-9 font-sans text-body-md text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                            <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                        </x-ui.modal-field>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Alamat Asal" name="edit_alamat_asal" icon="trip_origin"
                            :error="$errors->first('alamat_asal')">
                            <input type="text" id="edit_alamat_asal" name="alamat_asal" x-model="ruteDipilih.alamat_asal"
                                maxlength="150"
                                class="w-full rounded-lg bg-surface-container-low py-2 pl-9 pr-3 font-sans text-body-md text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Alamat Tujuan" name="edit_alamat_tujuan" icon="place"
                            :error="$errors->first('alamat_tujuan')">
                            <input type="text" id="edit_alamat_tujuan" name="alamat_tujuan" x-model="ruteDipilih.alamat_tujuan"
                                maxlength="150"
                                class="w-full rounded-lg bg-surface-container-low py-2 pl-9 pr-3 font-sans text-body-md text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-3">
                        <x-ui.modal-field label="Jarak Tempuh" name="edit_jarak" icon="straighten"
                            :error="$errors->first('jarak_km')">
                            <input type="text" id="edit_jarak" name="jarak_km" x-model="ruteDipilih.jarak_km"
                                inputmode="numeric"
                                class="w-full rounded-lg bg-surface-container-low py-2 pl-9 pr-12 text-right font-financial-table text-body-md font-bold tabular-nums text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <span class="pointer-events-none absolute right-3 font-sans text-body-sm font-semibold text-slate-text-muted">KM</span>
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Tarif per Ton" name="edit_tarif" :required="true"
                            :error="$errors->first('tarif_per_ton')">
                            <span class="pointer-events-none absolute left-3 font-sans text-body-md font-semibold text-slate-text-muted">Rp</span>
                            <input type="text" id="edit_tarif" name="tarif_per_ton" :value="Number(ruteDipilih.rate || 0).toLocaleString('id-ID')"
                                inputmode="numeric" required @input="formatRupiahInput($event)"
                                class="w-full rounded-lg bg-surface-container-low py-2 pl-10 pr-3 text-right font-financial-table text-body-md font-bold tabular-nums text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Uang Jalan Default" name="edit_uang_jalan" :required="true"
                            :error="$errors->first('uang_jalan_default')">
                            <span class="pointer-events-none absolute left-3 font-sans text-body-md font-semibold text-slate-text-muted">Rp</span>
                            <input type="text" id="edit_uang_jalan" name="uang_jalan_default" :value="Number(ruteDipilih.trip_money || 0).toLocaleString('id-ID')"
                                inputmode="numeric" required @input="formatRupiahInput($event)"
                                class="w-full rounded-lg bg-surface-container-low py-2 pl-10 pr-3 text-right font-financial-table text-body-md font-bold tabular-nums text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>
                    </div>

                    <div x-show="(ruteDipilih.ritase_count || 0) > 0" x-cloak class="flex items-start gap-space-md rounded-xl bg-warning-surface p-space-md">
                        <span class="material-symbols-outlined mt-0.5 shrink-0 text-[1.25rem] text-warning-amber">warning</span>
                        <div class="flex flex-col">
                            <span class="font-sans text-label-md font-bold text-slate-text-primary">Kebijakan Pembaruan Tarif</span>
                            <p class="mt-0.5 font-sans text-body-sm text-slate-text-secondary">
                                Rute ini sudah dipakai pada <strong class="text-slate-text-primary" x-text="(ruteDipilih.ritase_count || 0).toLocaleString('id-ID') + ' pengantaran'"></strong>.
                                Perubahan tarif tidak mengubah nilai pengantaran yang sudah selesai dan tercatat di slip gaji supir.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-space-sm bg-canvas-bg px-space-lg py-space-md shadow-inner">
                    <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm transition-all hover:bg-surface-container-low" @click="modalRuteEdit = false">
                        Batal
                    </button>
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-primary-container px-space-xl py-2.5 font-sans text-body-md font-semibold text-on-primary shadow-md transition-all hover:bg-primary active:scale-95">
                        <span class="material-symbols-outlined text-[1.125rem]">save</span>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- ============ MODAL DETAIL RUTE (read-only) ============ --}}
        <x-ui.modal-view
            name="modalRuteDetail"
            title="Detail Tarif Rute"
            subtitle="Informasi lengkap rute angkutan. Hanya dapat dibaca."
            icon="alt_route"
            :baris="$barisDetailRute"
        />

        {{-- ============ MODAL HAPUS TARIF RUTE ============ --}}
        <x-ui.modal-delete
            name="modalRuteHapus"
            title="Hapus Tarif Rute?"
            entity="rute"
            action-binding="'{{ url('admin/master/routes') }}/' + ruteDipilih.id"
            terkunci-binding="(ruteDipilih.ritase_count || 0) > 0"
            alasan-binding="'Rute ini dipakai pada ' + (ruteDipilih.ritase_count || 0) + ' pengantaran yang sudah selesai. Data tidak dapat dihapus.'"
            ringkasan-binding="[
                { label: 'Pengantaran Selesai', nilai: (ruteDipilih.ritase_count || 0) + ' Pengantaran' },
                { label: 'Total Tonase', nilai: (ruteDipilih.tonase || '0,00') + ' Ton' },
            ]"
            centang="setujuRuteHapus"
            status-binding="ruteDipilih.status"
            status-action-binding="'{{ url('admin/master/routes') }}/' + ruteDipilih.id + '/status'"
            label-status="Nonaktifkan Rute"
        />
    </div>
</x-app-layout>
