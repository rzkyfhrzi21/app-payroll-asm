@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');

    $barisDetailTruk = "[
        { label: 'Nomor Polisi', nilai: trukDipilih.nomor_polisi },
        { label: 'Jenis Truk', nilai: trukDipilih.nama_jenis },
        { label: 'Merek & Model', nilai: trukDipilih.merek_model },
        { label: 'Status Unit', nilai: trukDipilih.status === 'aktif' ? 'Aktif' : 'Nonaktif' },
        { label: 'Supir Pemegang', nilai: trukDipilih.nama_supir || 'Belum ada supir' },
        { label: 'NIP Supir', nilai: trukDipilih.nip_supir },
        { label: 'Pengantaran Selesai', nilai: (trukDipilih.ritase_count || 0) + ' pengantaran' },
        { label: 'Total Tonase Diangkut', nilai: (trukDipilih.tonase || '0,00') + ' Ton' },
    ]";

    $adaFilterAktif = $filter['cari'] !== '' || $filter['status'] !== 'all' || $filter['penugasan'] !== 'all';
@endphp

<x-app-layout title="Data Master — Unit Armada Truk">
    <div class="flex flex-col gap-6" x-data="masterPage()">
        <x-ui.page-header
            title="Data Master — Unit Armada Truk"
            breadcrumb="Data Master / Armada"
            subtitle="Daftar unit truk pengangkutan beserta jenis, supir pemegang, dan total tonase yang sudah diangkut."
        >
            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-surface-container-lowest px-4 py-2 font-sans text-body-sm font-semibold text-slate-text-primary shadow-sm transition-all hover:bg-surface-container" @click="modalJenis = true">
                <span class="material-symbols-outlined text-[1.125rem] text-slate-text-secondary">category</span>
                Kelola Jenis Truk
            </button>
            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-primary-container px-4 py-2 font-sans text-body-sm font-semibold text-on-primary shadow-md transition-all hover:bg-primary" @click="modalTrukTambah = true">
                <span class="material-symbols-outlined text-[1.125rem]">add_circle</span>
                Tambah Unit Armada
            </button>
        </x-ui.page-header>

        {{-- Diagram komposisi + statistik --}}
        <div class="grid grid-cols-1 items-start gap-space-lg xl:grid-cols-12">
            <div class="xl:col-span-5">
                <x-ui.donut-chart
                    :items="$truckComposition"
                    :total="$summary['total']"
                    label="Unit"
                    judul="Komposisi Model Armada"
                />
            </div>

            <div class="grid grid-cols-2 gap-space-md xl:col-span-7">
                <x-ui.stat-tile
                    label="Total Unit"
                    :nilai="$summary['total']"
                    satuan="unit"
                    ikon="local_shipping"
                    tone="primary"
                    :progres="$summary['total'] > 0 ? round($summary['dengan_supir'] / $summary['total'] * 100) : 0"
                    :progres-label="$summary['dengan_supir'].' unit sudah ada supir'"
                />
                <x-ui.stat-tile
                    label="Status Aktif"
                    :nilai="$summary['aktif']"
                    satuan="aktif"
                    ikon="check_circle"
                    tone="emerald"
                    :progres="$summary['total'] > 0 ? round($summary['aktif'] / $summary['total'] * 100) : 0"
                    :progres-label="$summary['nonaktif'].' unit nonaktif'"
                />
                <x-ui.stat-tile
                    label="Tanpa Supir"
                    :nilai="$summary['tanpa_supir']"
                    satuan="unit"
                    ikon="person_off"
                    :tone="$summary['tanpa_supir'] > 0 ? 'amber' : 'emerald'"
                    :catatan="$summary['tanpa_supir'] > 0 ? 'Perlu penugasan supir' : 'Semua unit sudah ditugaskan'"
                />
                <x-ui.stat-tile
                    label="Tonase Diangkut"
                    :nilai="$summary['total_tonase']"
                    satuan="ton"
                    ikon="scale"
                    tone="slate"
                    :catatan="number_format($summary['total_ritase'], 0, ',', '.').' pengantaran selesai'"
                />
            </div>
        </div>

        {{-- Tabel unit --}}
        <div class="flex flex-col overflow-hidden rounded-xl bg-surface-container-lowest shadow-card">
            <div class="flex flex-col gap-space-md border-b border-card-border p-space-md">
                <div class="flex flex-wrap items-center justify-between gap-space-sm">
                    <div>
                        <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Tabel Unit Armada</h2>
                        <p class="font-sans text-body-sm text-slate-text-secondary">
                            Total tonase = akumulasi seluruh angkutan yang sudah selesai diantarkan oleh unit ini.
                        </p>
                    </div>
                    <span class="rounded-full bg-surface-container px-2.5 py-0.5 font-sans text-label-sm font-semibold text-primary">{{ $summary['total'] }} Unit</span>
                </div>

                <form
                    method="GET"
                    action="{{ route('admin.master.trucks.index') }}"
                    data-filter-server
                    class="flex flex-col items-center justify-between gap-space-sm md:flex-row"
                >
                    <div class="relative w-full md:w-80">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[1.125rem] text-slate-text-muted">search</span>
                        <input
                            type="text"
                            name="cari"
                            value="{{ $filter['cari'] }}"
                            data-filter-debounce
                            class="w-full rounded-lg bg-surface py-2 pl-9 pr-4 font-sans text-body-sm text-slate-text-primary placeholder:text-slate-text-muted focus:outline-none focus:ring-2 focus:ring-primary-container"
                            placeholder="Cari nopol, model truk, supir..."
                        >
                    </div>

                    <div class="flex w-full flex-wrap items-center gap-space-sm md:w-auto">
                        <select name="status" data-filter-submit class="rounded-lg bg-surface px-3 py-2 font-sans text-body-sm text-slate-text-secondary focus:outline-none focus:ring-2 focus:ring-primary-container">
                            <option value="all">Status: Semua</option>
                            <option value="aktif" @selected($filter['status'] === 'aktif')>Status: Aktif</option>
                            <option value="nonaktif" @selected($filter['status'] === 'nonaktif')>Status: Nonaktif</option>
                        </select>

                        <select name="penugasan" data-filter-submit class="rounded-lg bg-surface px-3 py-2 font-sans text-body-sm text-slate-text-secondary focus:outline-none focus:ring-2 focus:ring-primary-container">
                            <option value="all">Penugasan: Semua</option>
                            <option value="terpakai" @selected($filter['penugasan'] === 'terpakai')>Sudah Ada Supir</option>
                            <option value="kosong" @selected($filter['penugasan'] === 'kosong')>Tanpa Supir</option>
                        </select>

                        @if ($adaFilterAktif)
                            <a
                                href="{{ route('admin.master.trucks.index') }}"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 font-sans text-body-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100"
                            >
                                <span class="material-symbols-outlined text-[1rem]">restart_alt</span>
                                <span>Reset Filter</span>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <x-ui.tabel-massal
                url="{{ route('admin.master.trucks.status-massal') }}"
                :opsi-status="\App\Models\Truk::daftarStatus()"
                label="unit armada"
                keterangan="Pilih status unit…"
            >
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider">
                            <th class="w-14 px-4 py-3 text-center font-semibold text-slate-text-secondary">No</th>
                            <th class="w-10 px-2 py-3 text-center font-semibold text-slate-text-secondary">
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
                                    :checked="semuaTerpilih"
                                    @change="pilihSemuaBaris($event.target.checked)"
                                    aria-label="Pilih semua unit armada"
                                >
                            </th>
                            <th class="px-4 py-3 font-semibold text-slate-text-secondary">Nomor Polisi</th>
                            <th class="px-4 py-3 font-semibold text-slate-text-secondary">Jenis</th>
                            <th class="px-4 py-3 font-semibold text-slate-text-secondary">Merek &amp; Model</th>
                            <th class="px-4 py-3 font-semibold text-slate-text-secondary">Supir Pemegang</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-text-secondary">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-text-secondary">Pengantaran Selesai</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-text-secondary">Total Tonase Diangkut</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-text-secondary">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-card-border font-sans text-financial-table">
                        @forelse ($trucks as $truk)
                            <tr class="transition-colors hover:bg-surface-container-low/60">
                                <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $trucks->firstItem() + $loop->index }}</td>
                                <td class="px-2 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        value="{{ $truk['id'] }}"
                                        x-model="terpilih"
                                        data-centang-massal
                                        class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
                                        aria-label="Pilih unit {{ $truk['nomor_polisi'] }}"
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <button type="button" class="font-display text-headline-sm font-semibold tabular-nums text-primary hover:underline" @click="bukaDetailTruk(@js($truk))">{{ $truk['nomor_polisi'] }}</button>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($truk['nama_jenis'] !== '')
                                        <span class="rounded bg-surface-container-high px-2 py-0.5 font-sans text-label-sm font-semibold text-primary">{{ $truk['nama_jenis'] }}</span>
                                    @else
                                        <span class="font-sans text-label-sm text-slate-text-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-text-primary">{{ $truk['merek_model'] }}</td>
                                <td class="px-4 py-3">
                                    @if ($truk['nama_supir'] !== '')
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-slate-text-primary">{{ $truk['nama_supir'] }}</span>
                                            <span class="font-sans text-label-sm text-slate-text-muted">{{ $truk['nip_supir'] }}</span>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-warning-surface px-2 py-0.5 font-sans text-label-sm font-semibold text-warning-amber">
                                            <span class="material-symbols-outlined text-[0.875rem]">person_off</span>
                                            Belum ada supir
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-bold {{ $truk['status'] === 'aktif' ? 'bg-net-pay-surface text-net-pay-emerald' : 'bg-surface-container text-slate-text-secondary' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $truk['status'] === 'aktif' ? 'bg-net-pay-emerald' : 'bg-slate-text-muted' }}"></span>
                                        {{ ucfirst($truk['status']) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-text-primary">{{ number_format($truk['ritase_count'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-text-primary">{{ $truk['tonase'] }} Ton</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" class="btn-aksi-tabel hover:text-slate-text-primary" title="Detail Unit" @click="bukaDetailTruk(@js($truk))">
                                            <span class="material-symbols-outlined text-[1.125rem]">visibility</span>
                                        </button>
                                        <button type="button" class="btn-aksi-tabel hover:text-primary" title="Ubah Unit" @click="bukaEditTruk(@js($truk))">
                                            <span class="material-symbols-outlined text-[1.125rem]">edit</span>
                                        </button>
                                        <button type="button" class="btn-aksi-tabel hover:text-danger-red" title="Hapus Unit" @click="bukaHapusTruk(@js($truk))">
                                            <span class="material-symbols-outlined text-[1.125rem]">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="11" label="unit armada" :tampilkan-reset="$adaFilterAktif" />
                        @endforelse
                    </tbody>
                </table>
            </div>
            </x-ui.tabel-massal>

            <x-ui.table-pagination :paginator="$trucks" :per-page="$perPage" label="unit armada" />
        </div>

        {{-- ============ MODAL TAMBAH UNIT ============ --}}
        <x-ui.modal-shell name="modalTrukTambah" title="Tambah Unit Armada Truk" subtitle="Registrasi unit baru beserta jenis dan supir pemegang." icon="local_shipping" accent="gradient" badge="Formulir Baru" max-width="2xl">
            <form method="POST" action="{{ route('admin.master.trucks.store') }}" enctype="multipart/form-data" class="flex flex-col overflow-hidden">
                @csrf
                <div class="flex max-h-[65vh] flex-col gap-space-md overflow-y-auto px-space-lg pb-space-md">
                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Nomor Polisi" name="nomor_polisi" :required="true" icon="badge" hint="Format: BE 8639 AAU." :error="$errors->first('nomor_polisi')">
                            <input type="text" id="nomor_polisi" name="nomor_polisi" value="{{ old('nomor_polisi') }}" maxlength="15" placeholder="BE 8639 AAU" required @input="paksaHurufBesar($event)"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-financial-table text-body-md font-semibold tracking-wide text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Jenis Truk" name="id_jenis_truk" :required="true" icon="category" hint="Atur daftar jenis lewat tombol Kelola Jenis Truk." :error="$errors->first('id_jenis_truk')">
                            <select id="id_jenis_truk" name="id_jenis_truk" required @change="pilihJenisTambah($event)"
                                class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">— Pilih jenis —</option>
                                @foreach ($jenisTruk as $jenis)
                                    <option value="{{ $jenis['id'] }}" data-model="{{ $jenis['merek_model'] }}" @selected(old('id_jenis_truk') == $jenis['id'])>{{ $jenis['nama'] }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                        </x-ui.modal-field>
                    </div>

                    <x-ui.modal-field label="Merek & Model Truk" name="merek_model" :required="true" icon="local_shipping" hint="Terisi otomatis dari jenis, tetap bisa disesuaikan." :error="$errors->first('merek_model')">
                        <input type="text" id="merek_model" name="merek_model" value="{{ old('merek_model') }}" maxlength="100" placeholder="Hino 500 TI Putih" required
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                    </x-ui.modal-field>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">

                        <x-ui.modal-field label="Status Unit" name="status" :required="true" icon="toggle_on" :error="$errors->first('status')">
                            <select id="status" name="status" required class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="aktif" @selected(old('status', 'aktif') === 'aktif')>Aktif</option>
                                <option value="nonaktif" @selected(old('status') === 'nonaktif')>Nonaktif</option>
                            </select>
                            <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                        </x-ui.modal-field>
                    </div>

                    <x-ui.modal-field label="Supir Pemegang Unit" name="id_karyawan" icon="person" hint="Supir yang belum memegang unit ditampilkan lebih dulu." :error="$errors->first('id_karyawan')">
                        <select id="id_karyawan" name="id_karyawan" class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">— Belum ada supir —</option>
                            @if ($drivers['belum_pegang'] !== [])
                                <optgroup label="Belum memegang unit (disarankan)">
                                    @foreach ($drivers['belum_pegang'] as $supir)
                                        <option value="{{ $supir['id'] }}" @selected(old('id_karyawan') == $supir['id'])>{{ $supir['nama'] }} ({{ $supir['nip'] }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if ($drivers['sudah_pegang'] !== [])
                                <optgroup label="Sudah memegang unit lain">
                                    @foreach ($drivers['sudah_pegang'] as $supir)
                                        <option value="{{ $supir['id'] }}" @selected(old('id_karyawan') == $supir['id'])>{{ $supir['nama'] }} ({{ $supir['nip'] }}) — {{ $supir['plate'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                    </x-ui.modal-field>

                    <x-ui.modal-field label="Foto Unit Armada" name="foto_unit" icon="photo_camera" meta="(Opsional)" hint="Maksimal 2MB" :error="$errors->first('foto_unit')">
                        <x-ui.upload-foto id="foto_unit" name="foto_unit" :ikon="true" />
                    </x-ui.modal-field>

                    <div class="flex items-start gap-2 rounded-lg bg-surface-container-low p-space-sm">
                        <span class="material-symbols-outlined text-[1.125rem] text-primary">info</span>
                        <p class="font-sans text-body-sm text-slate-text-secondary">Jumlah pengantaran dan total tonase dihitung otomatis dari data pengantaran.</p>
                    </div>
                </div>

                <div class="flex items-center justify-between bg-canvas-bg px-space-lg py-space-md shadow-inner">
                    <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm" @click="modalTrukTambah = false">Batal</button>
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-primary-container px-space-xl py-2.5 font-sans text-body-md font-semibold text-on-primary shadow-md hover:bg-primary">
                        <span class="material-symbols-outlined text-[1.125rem]">check_circle</span> Simpan Unit
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- ============ MODAL EDIT UNIT ============ --}}
        <x-ui.modal-shell name="modalTrukEdit" title="Ubah Unit Armada Truk" subtitle="Perbarui identitas unit, status, dan supir pemegang." icon="edit" max-width="2xl">
            <form method="POST" :action="'{{ url('admin/master/trucks') }}/' + trukDipilih.id" enctype="multipart/form-data" class="flex flex-col overflow-hidden">
                @csrf
                @method('PATCH')
                <div class="flex max-h-[65vh] flex-col gap-space-md overflow-y-auto px-space-lg pb-space-md">
                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Nomor Polisi" name="edit_nomor_polisi" :required="true" icon="badge" :error="$errors->first('nomor_polisi')">
                            <input type="text" id="edit_nomor_polisi" name="nomor_polisi" x-model="trukDipilih.nomor_polisi" maxlength="15" required @input="paksaHurufBesar($event)"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-financial-table text-body-md font-semibold tracking-wide text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Jenis Truk" name="edit_jenis" :required="true" icon="category" :error="$errors->first('id_jenis_truk')">
                            <select id="edit_jenis" name="id_jenis_truk" x-model="trukDipilih.id_jenis_truk" required class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                                @foreach ($jenisTruk as $jenis)
                                    <option value="{{ $jenis['id'] }}">{{ $jenis['nama'] }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                        </x-ui.modal-field>
                    </div>

                    <x-ui.modal-field label="Merek & Model Truk" name="edit_merek_model" :required="true" icon="local_shipping" :error="$errors->first('merek_model')">
                        <input type="text" id="edit_merek_model" name="merek_model" x-model="trukDipilih.merek_model" maxlength="100" required
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                    </x-ui.modal-field>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">

                        <x-ui.modal-field label="Status Unit" name="edit_status" :required="true" icon="toggle_on" :error="$errors->first('status')">
                            <select id="edit_status" name="status" x-model="trukDipilih.status" required class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                            <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                        </x-ui.modal-field>
                    </div>

                    <x-ui.modal-field label="Supir Pemegang Unit" name="edit_id_karyawan" icon="person" hint="Ganti supir yang bertugas memegang unit ini." :error="$errors->first('id_karyawan')">
                        <select id="edit_id_karyawan" name="id_karyawan" x-model="trukDipilih.id_karyawan" class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">— Belum ada supir —</option>
                            @if ($drivers['belum_pegang'] !== [])
                                <optgroup label="Belum memegang unit (disarankan)">
                                    @foreach ($drivers['belum_pegang'] as $supir)
                                        <option value="{{ $supir['id'] }}">{{ $supir['nama'] }} ({{ $supir['nip'] }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if ($drivers['sudah_pegang'] !== [])
                                <optgroup label="Sudah memegang unit lain">
                                    @foreach ($drivers['sudah_pegang'] as $supir)
                                        <option value="{{ $supir['id'] }}">{{ $supir['nama'] }} ({{ $supir['nip'] }}) — {{ $supir['plate'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                    </x-ui.modal-field>

                    <x-ui.modal-field label="Ganti Foto Unit Armada" name="edit_foto_unit" icon="photo_camera" meta="(Opsional)" hint="Maksimal 2MB" :error="$errors->first('foto_unit')">
                        <x-ui.upload-foto id="edit_foto_unit" name="foto_unit" :ikon="true" />
                    </x-ui.modal-field>

                    <div class="flex items-start gap-2 rounded-lg bg-surface-container-low p-space-sm">
                        <span class="material-symbols-outlined text-[1.125rem] text-primary">info</span>
                        <p class="font-sans text-body-sm text-slate-text-secondary">Perubahan identitas unit tidak mengubah pengantaran dan slip gaji yang sudah tercatat.</p>
                    </div>
                </div>

                <div class="flex items-center justify-between bg-canvas-bg px-space-lg py-space-md shadow-inner">
                    <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm" @click="modalTrukEdit = false">Batal</button>
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-primary-container px-space-xl py-2.5 font-sans text-body-md font-semibold text-on-primary shadow-md hover:bg-primary">
                        <span class="material-symbols-outlined text-[1.125rem]">save</span> Simpan Perubahan
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- ============ MODAL DETAIL UNIT ============ --}}
        <x-ui.modal-view name="modalTrukDetail" title="Detail Unit Armada" subtitle="Informasi lengkap unit. Hanya dapat dibaca." icon="local_shipping" :baris="$barisDetailTruk" />

        {{-- ============ MODAL HAPUS UNIT ============ --}}
        <x-ui.modal-delete
            name="modalTrukHapus"
            title="Hapus Unit Armada?"
            entity="unit armada"
            action-binding="'{{ url('admin/master/trucks') }}/' + trukDipilih.id"
            terkunci-binding="(trukDipilih.ritase_count || 0) > 0 || (trukDipilih.penggajian_count || 0) > 0"
            alasan-binding="'Unit ini dipakai pada ' + (trukDipilih.ritase_count || 0) + ' pengantaran dan ' + (trukDipilih.penggajian_count || 0) + ' slip gaji. Data tidak dapat dihapus.'"
            ringkasan-binding="[
                { label: 'Pengantaran Selesai', nilai: (trukDipilih.ritase_count || 0) + ' Pengantaran' },
                { label: 'Total Tonase', nilai: (trukDipilih.tonase || '0,00') + ' Ton' },
            ]"
            centang="setujuTrukHapus"
            status-binding="trukDipilih.status"
            status-action-binding="'{{ url('admin/master/trucks') }}/' + trukDipilih.id + '/status'"
            label-status="Nonaktifkan Unit"
        />

        {{-- ============ MODAL KELOLA JENIS TRUK ============ --}}
        <x-ui.modal-shell name="modalJenis" title="Kelola Jenis Truk" subtitle="Kategori jenis armada. Dipakai sebagai pilihan saat menambah unit." icon="category" max-width="2xl" :z-index="60">
            <div class="flex max-h-[65vh] flex-col gap-space-md overflow-y-auto px-space-lg pb-space-md">
                {{-- Form tambah jenis --}}
                <form method="POST" action="{{ route('admin.master.jenis-truk.store') }}" class="flex flex-col gap-space-sm rounded-xl bg-surface-container-low p-space-md">
                    @csrf
                    <span class="font-sans text-label-md font-bold uppercase tracking-wide text-primary">Tambah Jenis Baru</span>
                    <div class="grid grid-cols-1 gap-space-sm md:grid-cols-3">
                        <div class="flex flex-col gap-1">
                            <label for="jenis_nama" class="font-sans text-label-sm font-semibold text-slate-text-primary">Nama Jenis <span class="text-danger-red">*</span></label>
                            <input type="text" id="jenis_nama" name="nama" value="{{ old('nama') }}" maxlength="100" required placeholder="Hino Tronton"
                                class="rounded-lg bg-card-bg px-3 py-2 font-sans text-body-sm text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="jenis_model" class="font-sans text-label-sm font-semibold text-slate-text-primary">Merek &amp; Model <span class="text-danger-red">*</span></label>
                            <input type="text" id="jenis_model" name="merek_model" value="{{ old('merek_model') }}" maxlength="100" required placeholder="Hino 500 TI Putih"
                                class="rounded-lg bg-card-bg px-3 py-2 font-sans text-body-sm text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="jenis_status" class="font-sans text-label-sm font-semibold text-slate-text-primary">Status</label>
                            <select id="jenis_status" name="status" class="rounded-lg bg-card-bg px-3 py-2 font-sans text-body-sm text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-primary-container px-4 py-2 font-sans text-label-md font-semibold text-on-primary shadow-sm hover:bg-primary">
                            <span class="material-symbols-outlined text-[1rem]">add_circle</span> Tambah Jenis
                        </button>
                    </div>
                </form>

                {{-- Daftar jenis --}}
                <div class="overflow-hidden rounded-xl border border-card-border">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider">
                                <th class="w-12 px-3 py-2 text-center font-semibold text-slate-text-secondary">No</th>
                                <th class="px-3 py-2 font-semibold text-slate-text-secondary">Nama Jenis</th>
                                <th class="px-3 py-2 font-semibold text-slate-text-secondary">Merek &amp; Model</th>
                                <th class="px-3 py-2 text-right font-semibold text-slate-text-secondary">Total Unit</th>
                                <th class="px-3 py-2 text-right font-semibold text-slate-text-secondary">Unit Terpakai</th>
                                <th class="px-3 py-2 text-right font-semibold text-slate-text-secondary">Menganggur</th>
                                <th class="px-3 py-2 text-center font-semibold text-slate-text-secondary">Status</th>
                                <th class="px-3 py-2 text-center font-semibold text-slate-text-secondary">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-card-border font-sans text-financial-table">
                            @forelse ($jenisTrukSemua as $jenis)
                                <tr class="hover:bg-surface-container-low/60">
                                    <td class="px-3 py-2 text-center tabular-nums text-slate-text-muted">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2 font-semibold text-slate-text-primary">{{ $jenis->nama }}</td>
                                    <td class="px-3 py-2 text-slate-text-secondary">{{ $jenis->merek_model }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums font-semibold text-slate-text-primary">{{ $jenis->truk_count }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-net-pay-surface px-2 py-0.5 font-sans text-label-sm font-bold tabular-nums text-net-pay-emerald">
                                            <span class="material-symbols-outlined text-[0.75rem]">person</span>
                                            {{ $jenis->truk_terpakai_count }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-bold tabular-nums {{ $jenis->truk_menganggur_count > 0 ? 'bg-warning-surface text-warning-amber' : 'bg-surface-container text-slate-text-muted' }}">
                                            <span class="material-symbols-outlined text-[0.75rem]">person_off</span>
                                            {{ $jenis->truk_menganggur_count }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-bold {{ $jenis->status === 'aktif' ? 'bg-net-pay-surface text-net-pay-emerald' : 'bg-surface-container text-slate-text-secondary' }}">
                                            {{ ucfirst($jenis->status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button" class="rounded p-1 text-slate-text-secondary hover:bg-surface-container-high hover:text-primary" title="Ubah Jenis" @click="bukaEditJenis(@js(['id' => $jenis->id, 'nama' => $jenis->nama, 'merek_model' => $jenis->merek_model, 'status' => $jenis->status]))">
                                                <span class="material-symbols-outlined text-[1rem]">edit</span>
                                            </button>
                                            <button type="button" class="rounded p-1 text-slate-text-secondary hover:bg-surface-container-high hover:text-danger-red" title="Hapus Jenis" @click="bukaHapusJenis(@js(['id' => $jenis->id, 'nama' => $jenis->nama, 'unit' => $jenis->truk_count]))">
                                                <span class="material-symbols-outlined text-[1rem]">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-3 py-6 text-center font-sans text-body-sm text-slate-text-muted">Belum ada jenis truk.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end bg-canvas-bg px-space-lg py-space-md shadow-inner">
                <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm" @click="modalJenis = false">Tutup</button>
            </div>
        </x-ui.modal-shell>

        {{-- Modal ubah jenis --}}
        <x-ui.modal-shell name="modalJenisEdit" title="Ubah Jenis Truk" icon="edit" max-width="lg" :z-index="70">
            <form method="POST" :action="'{{ url('admin/master/jenis-truk') }}/' + jenisDipilih.id" class="flex flex-col">
                @csrf
                @method('PATCH')
                <div class="flex flex-col gap-space-md px-space-lg pb-space-md">
                    <x-ui.modal-field label="Nama Jenis" name="jenis_edit_nama" :required="true" icon="category">
                        <input type="text" id="jenis_edit_nama" name="nama" x-model="jenisDipilih.nama" maxlength="100" required
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                    </x-ui.modal-field>
                    <x-ui.modal-field label="Merek & Model" name="jenis_edit_model" :required="true" icon="local_shipping">
                        <input type="text" id="jenis_edit_model" name="merek_model" x-model="jenisDipilih.merek_model" maxlength="100" required
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                    </x-ui.modal-field>
                    <x-ui.modal-field label="Status" name="jenis_edit_status" :required="true" icon="toggle_on">
                        <select id="jenis_edit_status" name="status" x-model="jenisDipilih.status" required class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                        <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                    </x-ui.modal-field>
                </div>
                <div class="flex items-center justify-between bg-canvas-bg px-space-lg py-space-md shadow-inner">
                    <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm" @click="modalJenisEdit = false">Batal</button>
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-primary-container px-space-xl py-2.5 font-sans text-body-md font-semibold text-on-primary shadow-md hover:bg-primary">
                        <span class="material-symbols-outlined text-[1.125rem]">save</span> Simpan
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- Modal hapus jenis --}}
        <x-ui.modal-delete
            name="modalJenisHapus"
            title="Hapus Jenis Truk?"
            entity="jenis truk"
            action-binding="'{{ url('admin/master/jenis-truk') }}/' + jenisDipilih.id"
            terkunci-binding="(jenisDipilih.unit || 0) > 0"
            alasan-binding="'Jenis ini dipakai oleh ' + (jenisDipilih.unit || 0) + ' unit. Data tidak dapat dihapus.'"
            ringkasan-binding="[{ label: 'Unit Tertaut', nilai: (jenisDipilih.unit || 0) + ' unit' }]"
            centang="setujuJenisHapus"
            :z-index="70"
        />
    </div>
</x-app-layout>
