<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN MASTER SUPIR & KARYAWAN):
     - Tujuan Halaman: Buku induk data karyawan & supir truk tambang batubara.
     - Analogi Dunia Nyata: Seperti lemari arsip kartu pegawai di kantor personalia HRD:
       mencatat foto supir, NIP, nomor HP, plat truk yang dipegang, nomor rekening bank untuk
       transfer gaji, serta besaran potongan BPJS Ketenagakerjaan bulanan.
     - Standar Aturan Master Data (Sesuai AGENTS.md Bab 3.3 & 3.5):
       * Normalisasi teks otomatis HURUF BESAR saat disimpan ke database via model `Karyawan`.
       * Data supir yang memiliki jejak riwayat transaksi (pernah jalan ritase, punya kasbon,
         atau tercatat di penggajian) TIDAK BOLEH dihapus sembarangan (tombol hapus dikunci).
       * Sedia modal kelola akun login untuk menghubungkan supir dengan akun pengguna (`User`).
     - Data dari Controller (Admin/Master/EmployeeController@index):
       * $summary: Total supir aktif, unit armada siap jalan, dan akumulasi ritase.
       * $drivers: Paginator daftar supir dari tabel `karyawan` join `truk` dan `users`.
       * $trucks: Daftar master unit truk untuk pilihan dropdown kendaraan.
     ============================================================================== -->
@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $statusTones = [
        'emerald' => 'bg-net-pay-surface text-net-pay-emerald',
        'amber' => 'bg-warning-surface text-warning-amber',
        'blue' => 'bg-surface-container text-primary',
        'red' => 'bg-danger-surface text-danger-red',
    ];

    // Daftar baris untuk modal detail. Ditulis sebagai teks JS agar aman
    // dipakai di atribut Alpine tanpa masalah escape tanda kutip.
    $barisDetailSupir = "[
        { label: 'Nomor Induk Karyawan', nilai: supirDipilih.nip },
        { label: 'Nama Lengkap', nilai: supirDipilih.name },
        { label: 'Status Kepegawaian', nilai: supirDipilih.status },
        { label: 'Nomor Telepon', nilai: supirDipilih.phone },
        { label: 'Email Login', nilai: supirDipilih.email },
        { label: 'Unit Armada Dipegang', nilai: supirDipilih.plate },
        { label: 'Model Unit Armada', nilai: supirDipilih.truck },
        { label: 'Nama Bank', nilai: supirDipilih.nama_bank },
        { label: 'Nomor Rekening', nilai: supirDipilih.nomor_rekening },
        { label: 'Skema Komisi', nilai: supirDipilih.commission },
        { label: 'Potongan BPJS Bulanan', nilai: 'Rp ' + Number(supirDipilih.bpjs || 0).toLocaleString('id-ID') },
        { label: 'Total Pengantaran Tercatat', nilai: supirDipilih.jumlah_ritase + ' pengantaran' },
    ]";

    $adaFilterAktif = $filter['cari'] !== '' || $filter['status'] !== 'all' || $filter['unit'] !== 'all';
@endphp

<x-app-layout title="Data Master - Supir">
    <div class="flex flex-col gap-6" x-data="masterPage([], @js($summary))" data-url-detail-supir="{{ url('admin/master/employees') }}">
        <x-ui.page-header
            title="Data Supir"
            breadcrumb="Data Master / Supir"
            subtitle="Basis data supir batubara: identitas, unit armada yang dipegang, skema komisi, dan rekening payroll."
        >
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-surface-container-lowest px-3.5 py-2.5 font-sans text-body-sm font-semibold text-primary shadow-sm ring-1 ring-card-border transition-all hover:bg-surface-container-low"
                @click="bukaModalAkun()"
            >
                <span class="material-symbols-outlined text-[1.125rem]">manage_accounts</span>
                <span>Kelola Akun Pengguna</span>
                <span class="rounded-full bg-primary/10 px-2 py-0.5 font-sans text-label-sm font-bold text-primary">{{ count($daftarAkun ?? []) }}</span>
            </button>

            <a
                href="{{ route('admin.operational.loans.index') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-surface-container-lowest px-3.5 py-2.5 font-sans text-body-sm font-medium text-slate-text-primary shadow-sm transition-all hover:bg-surface-container-low"
            >
                <span class="material-symbols-outlined text-[1.125rem] text-slate-text-secondary">payments</span>
                Kasbon &amp; Potongan
            </a>

            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-container px-4 py-2.5 font-sans text-body-sm font-semibold text-on-primary shadow-md transition-all hover:bg-primary active:scale-95"
                @click="bukaTambah('driver')"
            >
                <span class="material-symbols-outlined text-[1.25rem]">person_add</span>
                Tambah Driver Baru
            </button>
        </x-ui.page-header>

        {{-- Ringkasan supir --}}
        <div class="grid grid-cols-2 gap-space-lg lg:grid-cols-4">
            <div class="rounded-xl bg-surface-container-lowest p-space-lg shadow-card">
                <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Total Supir</span>
                <div class="mt-1 font-display text-financial-total font-bold tabular-nums text-slate-text-primary">{{ $summary['drivers'] }}</div>
                <p class="mt-1 font-sans text-body-sm text-slate-text-secondary">Personel terdaftar</p>
            </div>
            <div class="rounded-xl bg-surface-container-lowest p-space-lg shadow-card">
                <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Aktif</span>
                <div class="mt-1 font-display text-financial-total font-bold tabular-nums text-net-pay-emerald">{{ $summary['drivers_aktif'] }}</div>
                <p class="mt-1 font-sans text-body-sm text-slate-text-secondary">Siap ditugaskan</p>
            </div>
            <div class="rounded-xl bg-surface-container-lowest p-space-lg shadow-card">
                <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Nonaktif</span>
                <div class="mt-1 font-display text-financial-total font-bold tabular-nums text-slate-text-muted">{{ $summary['drivers_nonaktif'] }}</div>
                <p class="mt-1 font-sans text-body-sm text-slate-text-secondary">Tidak bertugas</p>
            </div>
            <div class="rounded-xl bg-surface-container-lowest p-space-lg shadow-card">
                <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Belum Dapat Unit</span>
                <div class="mt-1 font-display text-financial-total font-bold tabular-nums text-warning-amber">{{ $summary['drivers_tanpa_unit'] }}</div>
                <p class="mt-1 font-sans text-body-sm text-slate-text-secondary">Perlu penugasan armada</p>
            </div>
        </div>

        {{-- Tabel supir --}}
        <div data-wadah-tabel="karyawan" class="flex flex-col overflow-hidden rounded-xl bg-surface-container-lowest shadow-card">
            <div
                class="flex flex-col items-center justify-between gap-space-md border-b border-card-border p-space-md md:flex-row"
            >
                <div class="relative w-full md:w-80">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[1.125rem] text-slate-text-muted">search</span>
                    <input
                        type="text"
                        name="cari"
                        value="{{ $filter['cari'] }}"
                        class="w-full rounded-lg bg-surface py-2 pl-9 pr-4 font-sans text-body-sm text-slate-text-primary placeholder:text-slate-text-muted focus:outline-none focus:ring-2 focus:ring-primary-container"
                        placeholder="Cari nama supir, NIP, plat truk..."
                    >
                </div>

                <div class="flex w-full flex-wrap items-center gap-space-sm md:w-auto">
                    <select name="status" class="rounded-lg bg-surface px-3 py-2 font-sans text-body-sm text-slate-text-secondary focus:outline-none focus:ring-2 focus:ring-primary-container">
                        <option value="all">Status: Semua</option>
                        <option value="aktif" @selected($filter['status'] === 'aktif')>Status: Aktif</option>
                        <option value="nonaktif" @selected($filter['status'] === 'nonaktif')>Status: Nonaktif</option>
                    </select>

                    <select name="unit" class="rounded-lg bg-surface px-3 py-2 font-sans text-body-sm text-slate-text-secondary focus:outline-none focus:ring-2 focus:ring-primary-container">
                        <option value="all">Armada: Semua</option>
                        <option value="pegang" @selected($filter['unit'] === 'pegang')>Sudah Pegang Unit</option>
                        <option value="belum" @selected($filter['unit'] === 'belum')>Belum Dapat Unit</option>
                    </select>

                    <button
                        type="button"
                        data-tombol-reset-filter
                        onclick="window.resetTabelAjax('karyawan')"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 font-sans text-body-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100 {{ $adaFilterAktif ? '' : 'hidden' }}"
                    >
                        <span class="material-symbols-outlined text-[1rem]">restart_alt</span>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            <x-ui.tabel-massal
                url="{{ route('admin.master.employees.status-massal') }}"
                :opsi-status="\App\Models\Karyawan::daftarStatus()"
                label="supir"
                keterangan="Pilih status supir…"
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
                                    aria-label="Pilih semua supir"
                                >
                            </th>
                            <th class="w-14 px-4 py-3 text-center font-semibold text-slate-text-secondary">No</th>
                            <th class="px-4 py-3 font-semibold text-slate-text-secondary">Supir &amp; Identitas</th>
                            <th class="px-4 py-3 font-semibold text-slate-text-secondary">Unit Truk Dipegang</th>
                            <th class="px-4 py-3 font-semibold text-slate-text-secondary">Rekening Payroll</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-text-secondary">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-text-secondary">Sisa Kasbon</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-text-secondary">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="tbodyKaryawan" class="divide-y divide-card-border font-sans text-financial-table">
                        @foreach ($employees as $indeksBaris => $employee)
                            @include('components.tabel-baris.karyawan', ['baris' => $employee, 'nomor' => $employees->firstItem() + $indeksBaris])
                        @endforeach

                        @if ($employees->isEmpty())
                            <x-ui.table-empty :colspan="8" label="data supir" :tampilkan-reset="$adaFilterAktif" />
                        @endif
                    </tbody>
            </div>
            </x-ui.tabel-massal>

            <x-ui.tabel-ajax
                entitas="karyawan"
                label="supir"
                target-id="tbodyKaryawan"
                :kolom="8"
                :filter="$filter"
                :per-halaman-awal="$perPage"
            />
        </div>

        {{-- ============ MODAL TAMBAH SUPIR ============ --}}
        <x-ui.modal-shell
            name="modalSupirTambah"
            title="Tambah Data Supir"
            subtitle="Registrasi pengemudi baru, akun portal supir, dan rekening payroll."
            icon="person_add"
            badge="Formulir Baru"
            max-width="2xl"
        >
            <form method="POST" action="{{ route('admin.master.employees.store') }}" enctype="multipart/form-data" class="flex flex-col overflow-hidden">
                @csrf
                <div class="flex max-h-[65vh] flex-col gap-space-md overflow-y-auto px-space-lg pb-space-md">
                    <x-ui.modal-field label="Nama Lengkap" name="nama_lengkap" :required="true" icon="person"
                        hint="Mengetik nama akan menyusun otomatis inisial Nomor Induk (DRF-INISIAL-2ANGKA)." :error="$errors->first('nama_lengkap')">
                        <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap') }}"
                            maxlength="150" placeholder="Contoh: Kuwat Slamet" required
                            @input="perbaruiNipDariNama($event.target.value)"
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary transition-all placeholder:text-slate-text-muted/60 focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                    </x-ui.modal-field>

                    <x-ui.modal-field label="Nomor Induk Karyawan" name="nomor_induk_karyawan" :required="true" icon="badge"
                        hint="Format standar: DRF-{INISIAL NAMA}-{2 ANGKA ACAK} (contoh: DRF-KS-48). Dapat diubah manual." :error="$errors->first('nomor_induk_karyawan')">
                        <input type="text" id="nomor_induk_karyawan" name="nomor_induk_karyawan" x-model="nipBaru"
                            maxlength="20" placeholder="DRF-KS-48" @input="nipDiubahManual = true; paksaHurufBesar($event)" required
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-28 font-financial-table text-body-md font-bold tracking-wide text-slate-text-primary transition-all placeholder:text-slate-text-muted/60 focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        <button type="button" @click="acakUlangNip()" title="Generate Ulang Nomor Induk"
                            class="absolute right-2 inline-flex items-center gap-1 rounded-md bg-primary/10 px-2.5 py-1 font-sans text-label-sm font-bold text-primary transition-colors hover:bg-primary hover:text-on-primary">
                            <span class="material-symbols-outlined text-[0.95rem]">autorenew</span>
                            <span>Acak NIP</span>
                        </button>
                    </x-ui.modal-field>

                    <x-ui.modal-field label="Status Kepegawaian" name="status" :required="true" icon="toggle_on"
                        :error="$errors->first('status')">
                        <select id="status" name="status" required
                            class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="aktif" @selected(old('status', 'aktif') === 'aktif')>Aktif</option>
                            <option value="nonaktif" @selected(old('status') === 'nonaktif')>Nonaktif</option>
                        </select>
                        <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                    </x-ui.modal-field>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Persentase Komisi (%)" name="persentase_komisi" icon="percent"
                            hint="Dihitung dari hasil bersih armada per periode." :error="$errors->first('persentase_komisi')">
                            <input type="text" id="persentase_komisi" name="persentase_komisi" x-model="komisiTambah"
                                inputmode="decimal" placeholder="10"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-12 font-financial-table text-body-md font-bold tabular-nums text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <span class="pointer-events-none absolute right-3 font-sans text-label-md font-bold text-slate-text-muted">%</span>
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Potongan BPJS Bulanan (Rp)" name="potongan_bpjs_bulanan" icon="health_and_safety"
                            hint="Nominal potongan BPJS Ketenagakerjaan per periode." :error="$errors->first('potongan_bpjs_bulanan')">
                            <input type="text" id="potongan_bpjs_bulanan" name="potongan_bpjs_bulanan" x-model="bpjsTambah"
                                inputmode="numeric" @input="formatRupiahInput($event); bpjsTambah = $event.target.value" placeholder="129.000"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-financial-table text-body-md font-bold tabular-nums text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Nama Bank" name="nama_bank" icon="account_balance" meta="(Opsional)"
                            :error="$errors->first('nama_bank')">
                            <input type="text" id="nama_bank" name="nama_bank" value="{{ old('nama_bank') }}"
                                list="daftar-bank" placeholder="BCA" maxlength="50"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <datalist id="daftar-bank">
                                <option value="BCA"></option>
                                <option value="BNI"></option>
                                <option value="BRI"></option>
                                <option value="MANDIRI"></option>
                            </datalist>
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Nomor Rekening" name="nomor_rekening" icon="credit_card" meta="(Opsional)"
                            :error="$errors->first('nomor_rekening')">
                            <input type="text" id="nomor_rekening" name="nomor_rekening" value="{{ old('nomor_rekening') }}"
                                inputmode="numeric" placeholder="8271928391" maxlength="30"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-financial-table text-body-md tabular-nums text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Akun Login Supir (Email)" name="email" :required="true" icon="mail"
                            hint="Dipakai masuk ke Portal Supir." :error="$errors->first('email')">
                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                maxlength="150" placeholder="nama@payroll.test" required
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Kata Sandi Awal (Bisa Diubah)" name="password" :required="true" icon="lock"
                            hint="Minimal 8 karakter, wajib memuat huruf, angka, dan karakter khusus." :error="$errors->first('password')">
                            <input :type="lihatPasswordBaru ? 'text' : 'password'" id="password_supir_baru" name="password" x-model="kataSandiBaru"
                                minlength="8" maxlength="100" required
                                pattern="(?=.*[A-Za-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                                title="Minimal 8 karakter, kombinasi huruf, angka, dan karakter khusus."
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-24 font-mono text-body-md font-semibold text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <div class="absolute right-2 flex items-center gap-1">
                                <button type="button" @click="lihatPasswordBaru = !lihatPasswordBaru" title="Tampilkan / Sembunyikan Sandi"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-text-secondary hover:bg-surface-container hover:text-slate-text-primary">
                                    <span class="material-symbols-outlined text-[1rem]" x-text="lihatPasswordBaru ? 'visibility_off' : 'visibility'"></span>
                                </button>
                                <button type="button" @click="acakKataSandiBaru()" title="Generate Sandi Acak Baru"
                                    class="inline-flex items-center gap-0.5 rounded-md bg-primary/10 px-2 py-1 font-sans text-label-sm font-bold text-primary transition-colors hover:bg-primary hover:text-on-primary">
                                    <span class="material-symbols-outlined text-[0.9rem]">autorenew</span>
                                    <span>Acak</span>
                                </button>
                            </div>
                        </x-ui.modal-field>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Nomor Telepon / WhatsApp" name="nomor_telepon" icon="call" meta="(Opsional)"
                            hint="Hanya angka dan diawali angka 8, tanpa angka 0. Contoh: 812-8930-1123." :error="$errors->first('nomor_telepon')">
                            <span class="pointer-events-none absolute left-9 font-sans text-body-sm font-bold text-slate-text-secondary">+62</span>
                            <input type="tel" id="nomor_telepon" name="nomor_telepon" value="{{ \App\Support\FormatNomorTelepon::keLokal(old('nomor_telepon')) }}"
                                inputmode="numeric" maxlength="12" placeholder="81289301123" pattern="8[0-9]{7,11}" title="Diawali angka 8, tanpa angka 0. Contoh: 81289301123" oninput="window.maskTeleponInput(event)"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-20 pr-3 font-financial-table text-body-md tabular-nums text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Foto Profil Supir" name="foto_profil" icon="photo_camera" meta="(Opsional)"
                            hint="Maksimal 2MB" :error="$errors->first('foto_profil')">
                            <x-ui.upload-foto id="foto_profil" name="foto_profil" :ikon="true" />
                        </x-ui.modal-field>
                    </div>

                    <div class="flex items-start gap-2 rounded-lg bg-surface-container-low p-space-sm">
                        <span class="material-symbols-outlined text-[1.125rem] text-primary">info</span>
                        <p class="font-sans text-body-sm text-slate-text-secondary">
                            Komisi dan BPJS diatur di halaman <strong class="text-slate-text-primary">Kasbon &amp; Potongan</strong>.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-space-sm bg-canvas-bg px-space-lg py-space-md shadow-inner">
                    <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm transition-all hover:bg-surface-container-low" @click="modalSupirTambah = false">
                        Batal
                    </button>
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-primary-container px-space-xl py-2.5 font-sans text-body-md font-semibold text-on-primary shadow-md transition-all hover:bg-primary active:scale-95">
                        <span class="material-symbols-outlined text-[1.125rem]">check_circle</span>
                        Simpan Data Supir
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- ============ MODAL EDIT SUPIR ============ --}}
        <x-ui.modal-shell
            name="modalSupirEdit"
            title="Ubah Data Supir"
            subtitle="Perbarui identitas, status kepegawaian, dan rekening payroll."
            icon="edit"
            max-width="2xl"
        >
            <form method="POST" :action="'{{ url('admin/master/employees') }}/' + supirDipilih.id" enctype="multipart/form-data" class="flex flex-col overflow-hidden">
                @csrf
                @method('PATCH')
                <div class="flex max-h-[65vh] flex-col gap-space-md overflow-y-auto px-space-lg pb-space-md">
                    <div class="rounded-lg bg-surface-container-low px-space-md py-space-sm">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-muted">Nomor Induk (tidak dapat diubah)</span>
                        <div class="font-financial-table text-body-md font-bold tabular-nums text-slate-text-primary" x-text="supirDipilih.nip"></div>
                    </div>

                    <x-ui.modal-field label="Nama Lengkap" name="edit_nama_lengkap" :required="true" icon="person"
                        :error="$errors->first('nama_lengkap')">
                        <input type="text" id="edit_nama_lengkap" name="nama_lengkap" x-model="supirDipilih.name"
                            maxlength="150" required
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                    </x-ui.modal-field>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Status Kepegawaian" name="edit_status" :required="true" icon="toggle_on"
                            :error="$errors->first('status')">
                            <select id="edit_status" name="status" x-model="supirDipilih.status_kode" required
                                class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                            <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                        </x-ui.modal-field>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Persentase Komisi (%)" name="edit_persentase_komisi" icon="percent"
                            :error="$errors->first('persentase_komisi')">
                            <input type="text" id="edit_persentase_komisi" name="persentase_komisi" x-model="supirDipilih.komisi_persen"
                                inputmode="decimal"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-12 font-financial-table text-body-md font-bold tabular-nums text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <span class="pointer-events-none absolute right-3 font-sans text-label-md font-bold text-slate-text-muted">%</span>
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Potongan BPJS Bulanan (Rp)" name="edit_potongan_bpjs_bulanan" icon="health_and_safety"
                            :error="$errors->first('potongan_bpjs_bulanan')">
                            <input type="text" id="edit_potongan_bpjs_bulanan" name="potongan_bpjs_bulanan" x-model="supirDipilih.bpjs"
                                inputmode="numeric"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-financial-table text-body-md font-bold tabular-nums text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Nama Bank" name="edit_bank" icon="account_balance" meta="(Opsional)"
                            :error="$errors->first('nama_bank')">
                            <input type="text" id="edit_bank" name="nama_bank" x-model="supirDipilih.nama_bank"
                                list="daftar-bank" maxlength="50"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Nomor Rekening" name="edit_rekening" icon="credit_card" meta="(Opsional)"
                            :error="$errors->first('nomor_rekening')">
                            <input type="text" id="edit_rekening" name="nomor_rekening" x-model="supirDipilih.nomor_rekening"
                                inputmode="numeric" maxlength="30"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-financial-table text-body-md tabular-nums text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Nomor Telepon / WhatsApp" name="edit_nomor_telepon" icon="call" meta="(Opsional)"
                            hint="Hanya angka dan diawali angka 8, tanpa angka 0." :error="$errors->first('nomor_telepon')">
                            <span class="pointer-events-none absolute left-9 font-sans text-body-sm font-bold text-slate-text-secondary">+62</span>
                            <input type="tel" id="edit_nomor_telepon" name="nomor_telepon" x-model="supirDipilih.telepon_lokal"
                                inputmode="numeric" maxlength="12" placeholder="81289301123" pattern="8[0-9]{7,11}" title="Diawali angka 8, tanpa angka 0. Contoh: 81289301123" oninput="window.maskTeleponInput(event)"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-20 pr-3 font-financial-table text-body-md tabular-nums text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Ganti Foto Profil" name="edit_foto_profil" icon="photo_camera" meta="(Opsional)"
                            hint="Maksimal 2MB">
                            <x-ui.upload-foto id="edit_foto_profil" name="foto_profil" :ikon="true" />
                        </x-ui.modal-field>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-space-sm bg-canvas-bg px-space-lg py-space-md shadow-inner">
                    <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm transition-all hover:bg-surface-container-low" @click="modalSupirEdit = false">
                        Batal
                    </button>
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-primary-container px-space-xl py-2.5 font-sans text-body-md font-semibold text-on-primary shadow-md transition-all hover:bg-primary active:scale-95">
                        <span class="material-symbols-outlined text-[1.125rem]">save</span>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- ============ MODAL DETAIL SUPIR (read-only, bersub tab) ============ --}}
        <x-ui.modal-shell
            name="modalSupirDetail"
            title="Detail Data Supir"
            subtitle="Identitas, pengeluaran operasional, dan kasbon supir."
            icon="badge"
            max-width="3xl"
        >
            <div class="flex max-h-[68vh] flex-col gap-space-md overflow-y-auto px-space-lg pb-space-md">
                {{-- Header ringkas supir --}}
                <div class="flex items-center gap-3 rounded-xl bg-surface-container-low p-space-md">
                    <img
                        :src="supirDipilih.foto_profil"
                        alt=""
                        class="h-12 w-12 shrink-0 rounded-full object-cover ring-1 ring-card-border"
                    >
                    <div class="flex min-w-0 flex-col">
                        <span class="truncate font-display text-headline-sm font-bold text-slate-text-primary" x-text="supirDipilih.name"></span>
                        <span class="truncate font-sans text-body-sm text-slate-text-secondary">
                            NIP <span x-text="supirDipilih.nip"></span> &bull; <span x-text="supirDipilih.plate"></span>
                        </span>
                    </div>
                </div>

                {{-- Sub tab --}}
                <div class="flex flex-wrap items-center gap-1 rounded-lg bg-surface-container-low p-1" role="tablist">
                    <button
                        type="button"
                        role="tab"
                        @click="tabDetailSupir = 'profil'"
                        :aria-selected="tabDetailSupir === 'profil'"
                        :class="tabDetailSupir === 'profil' ? 'bg-card-bg text-primary shadow-sm' : 'text-slate-text-secondary hover:text-slate-text-primary'"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-2 font-sans text-body-sm font-semibold transition-all"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">badge</span>
                        Profil
                    </button>
                    <button
                        type="button"
                        role="tab"
                        @click="tabDetailSupir = 'pengeluaran'; muatDetailSupir()"
                        :aria-selected="tabDetailSupir === 'pengeluaran'"
                        :class="tabDetailSupir === 'pengeluaran' ? 'bg-card-bg text-primary shadow-sm' : 'text-slate-text-secondary hover:text-slate-text-primary'"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-2 font-sans text-body-sm font-semibold transition-all"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">receipt_long</span>
                        Pengeluaran Operasional
                    </button>
                    <button
                        type="button"
                        role="tab"
                        @click="tabDetailSupir = 'kasbon'; muatDetailSupir()"
                        :aria-selected="tabDetailSupir === 'kasbon'"
                        :class="tabDetailSupir === 'kasbon' ? 'bg-card-bg text-primary shadow-sm' : 'text-slate-text-secondary hover:text-slate-text-primary'"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-2 font-sans text-body-sm font-semibold transition-all"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">account_balance_wallet</span>
                        Kasbon &amp; Potongan
                    </button>
                </div>

                {{-- Tab profil --}}
                <div x-show="tabDetailSupir === 'profil'" x-cloak class="flex flex-col gap-0.5">
                    <template x-for="(item, urutan) in {{ $barisDetailSupir }}" :key="urutan">
                        <div class="flex flex-col gap-0.5 border-b border-card-border pb-2 last:border-0">
                            <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-muted" x-text="item.label"></span>
                            <span class="break-words font-sans text-body-md font-semibold text-slate-text-primary" x-text="(item.nilai === null || item.nilai === '') ? '—' : item.nilai"></span>
                        </div>
                    </template>
                </div>

                {{-- Tab pengeluaran operasional --}}
                <div x-show="tabDetailSupir === 'pengeluaran'" x-cloak class="flex flex-col gap-space-sm">
                    <div class="grid grid-cols-2 gap-space-sm">
                        <div class="rounded-lg bg-surface-container-low px-3 py-2">
                            <span class="font-sans text-label-sm uppercase text-slate-text-muted">Total Pengeluaran</span>
                            <div class="font-display text-body-lg font-bold tabular-nums text-slate-text-primary" x-text="'Rp ' + Number(detailSupir.ringkasan.total_pengeluaran || 0).toLocaleString('id-ID')"></div>
                        </div>
                        <div class="rounded-lg bg-surface-container-low px-3 py-2">
                            <span class="font-sans text-label-sm uppercase text-slate-text-muted">Jumlah Item</span>
                            <div class="font-display text-body-lg font-bold tabular-nums text-slate-text-primary" x-text="(detailSupir.ringkasan.jumlah_pengeluaran || 0) + ' item'"></div>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-card-border">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider text-slate-text-secondary">
                                    <th class="px-3 py-2 font-semibold">Tanggal</th>
                                    <th class="px-3 py-2 font-semibold">Kategori</th>
                                    <th class="px-3 py-2 font-semibold">Unit</th>
                                    <th class="px-3 py-2 text-right font-semibold">Nominal</th>
                                    <th class="px-3 py-2 text-center font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-card-border font-sans text-financial-table">
                                <template x-for="baris in detailSupir.pengeluaran" :key="baris.id">
                                    <tr class="hover:bg-surface-container-low/60">
                                        <td class="whitespace-nowrap px-3 py-2 text-slate-text-secondary" x-text="baris.tanggal"></td>
                                        <td class="px-3 py-2">
                                            <p class="font-semibold text-slate-text-primary" x-text="baris.kategori"></p>
                                            <p class="font-sans text-label-sm text-slate-text-muted" x-text="baris.nomor_nota || '—'"></p>
                                        </td>
                                        <td class="px-3 py-2 tabular-nums text-slate-text-secondary" x-text="baris.plate"></td>
                                        <td class="px-3 py-2 text-right font-bold tabular-nums text-primary" x-text="baris.nominal_label"></td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="inline-flex rounded-full bg-surface-container px-2 py-0.5 font-sans text-label-sm font-bold text-slate-text-secondary" x-text="baris.status_label"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <p x-show="!memuatDetailSupir && detailSupir.pengeluaran.length === 0" class="py-6 text-center font-sans text-body-sm text-slate-text-muted">
                        Belum ada pengeluaran operasional untuk unit supir ini.
                    </p>
                    <p x-show="memuatDetailSupir" class="py-6 text-center font-sans text-body-sm text-slate-text-muted">Memuat data...</p>
                </div>

                {{-- Tab kasbon & potongan --}}
                <div x-show="tabDetailSupir === 'kasbon'" x-cloak class="flex flex-col gap-space-sm">
                    <div class="grid grid-cols-3 gap-space-sm">
                        <div class="rounded-lg bg-surface-container-low px-3 py-2">
                            <span class="font-sans text-label-sm uppercase text-slate-text-muted">Total Pinjaman</span>
                            <div class="font-display text-body-md font-bold tabular-nums text-slate-text-primary" x-text="'Rp ' + Number(detailSupir.ringkasan.total_kasbon || 0).toLocaleString('id-ID')"></div>
                        </div>
                        <div class="rounded-lg bg-surface-container-low px-3 py-2">
                            <span class="font-sans text-label-sm uppercase text-slate-text-muted">Sisa</span>
                            <div class="font-display text-body-md font-bold tabular-nums text-danger-red" x-text="'Rp ' + Number(detailSupir.ringkasan.sisa_kasbon || 0).toLocaleString('id-ID')"></div>
                        </div>
                        <div class="rounded-lg bg-surface-container-low px-3 py-2">
                            <span class="font-sans text-label-sm uppercase text-slate-text-muted">Jumlah Kasbon</span>
                            <div class="font-display text-body-md font-bold tabular-nums text-slate-text-primary" x-text="(detailSupir.ringkasan.jumlah_kasbon || 0) + ' kasbon'"></div>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-card-border">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider text-slate-text-secondary">
                                    <th class="px-3 py-2 font-semibold">Tanggal</th>
                                    <th class="px-3 py-2 text-right font-semibold">Pinjaman</th>
                                    <th class="px-3 py-2 text-center font-semibold">Cicilan</th>
                                    <th class="px-3 py-2 text-right font-semibold">Sisa</th>
                                    <th class="px-3 py-2 text-center font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-card-border font-sans text-financial-table">
                                <template x-for="baris in detailSupir.kasbon" :key="baris.id">
                                    <tr class="hover:bg-surface-container-low/60">
                                        <td class="whitespace-nowrap px-3 py-2 text-slate-text-secondary">
                                            <p x-text="baris.tanggal"></p>
                                            <p class="font-sans text-label-sm text-slate-text-muted" x-text="baris.keterangan || '—'"></p>
                                        </td>
                                        <td class="px-3 py-2 text-right font-semibold tabular-nums text-slate-text-primary" x-text="baris.total_label"></td>
                                        <td class="px-3 py-2 text-center">
                                            <p class="font-bold tabular-nums text-slate-text-primary" x-text="baris.angsuran_label"></p>
                                            <p class="font-sans text-label-sm text-slate-text-muted" x-text="baris.cicilan_selesai + '/' + baris.tenor + ' kali'"></p>
                                        </td>
                                        <td class="px-3 py-2 text-right font-bold tabular-nums" :class="baris.sisa > 0 ? 'text-danger-red' : 'text-net-pay-emerald'" x-text="baris.sisa > 0 ? baris.sisa_label : 'Lunas'"></td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="inline-flex rounded-full bg-warning-surface px-2 py-0.5 font-sans text-label-sm font-bold text-warning-amber" x-text="baris.status_label"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <p x-show="!memuatDetailSupir && detailSupir.kasbon.length === 0" class="py-6 text-center font-sans text-body-sm text-slate-text-muted">
                        Supir ini belum pernah mengajukan kasbon.
                    </p>
                    <p x-show="memuatDetailSupir" class="py-6 text-center font-sans text-body-sm text-slate-text-muted">Memuat data...</p>
                </div>
            </div>

            <div class="flex items-center justify-end bg-canvas-bg px-space-lg py-space-md shadow-inner">
                <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm transition-all hover:bg-surface-container-low" @click="modalSupirDetail = false">Tutup</button>
            </div>
        </x-ui.modal-shell>

        {{-- ============ MODAL HAPUS SUPIR ============ --}}
        <x-ui.modal-delete
            name="modalSupirHapus"
            title="Hapus Data Supir?"
            entity="supir"
            action-binding="'{{ url('admin/master/employees') }}/' + supirDipilih.id"
            terkunci-binding="(supirDipilih.jumlah_ritase || 0) > 0 || (supirDipilih.jumlah_penggajian || 0) > 0 || (supirDipilih.jumlah_kasbon || 0) > 0"
            alasan-binding="'Supir ini memiliki ' + [(supirDipilih.jumlah_ritase || 0) > 0 ? (supirDipilih.jumlah_ritase + ' pengantaran') : null, (supirDipilih.jumlah_penggajian || 0) > 0 ? (supirDipilih.jumlah_penggajian + ' slip gaji') : null, (supirDipilih.jumlah_kasbon || 0) > 0 ? (supirDipilih.jumlah_kasbon + ' kasbon') : null].filter(Boolean).join(', ') + '. Data tidak dapat dihapus.'"
            ringkasan-binding="[
                { label: 'Pengantaran', nilai: (supirDipilih.jumlah_ritase || 0) + ' Pengantaran' },
                { label: 'Slip Gaji', nilai: (supirDipilih.jumlah_penggajian || 0) + ' Slip' },
                { label: 'Kasbon', nilai: (supirDipilih.jumlah_kasbon || 0) + ' Kasbon' },
                { label: 'Sisa Kasbon', nilai: 'Rp ' + Number(supirDipilih.remaining || 0).toLocaleString('id-ID') },
            ]"
            centang="setujuSupirHapus"
            status-binding="supirDipilih.status_kode"
            status-action-binding="'{{ url('admin/master/employees') }}/' + supirDipilih.id + '/status'"
            label-status="Nonaktifkan Supir"
        />

        {{-- ============ MODAL KELOLA AKUN PENGGUNA ============ --}}
        <x-ui.modal-shell
            name="modalAkun"
            title="Kelola Akun Pengguna"
            subtitle="Akun staf untuk masuk aplikasi. Data supir dikelola pada tabel utama halaman ini."
            icon="manage_accounts"
            max-width="2xl"
            :z-index="60"
        >
            <div class="flex max-h-[68vh] flex-col gap-space-md overflow-y-auto px-space-lg pb-space-md">
                {{-- Form tambah akun --}}
                <form method="POST" action="{{ route('admin.master.akun.store') }}" class="flex flex-col gap-space-sm rounded-xl bg-surface-container-low p-space-md">
                    @csrf
                    <span class="font-sans text-label-md font-bold uppercase tracking-wide text-primary">Tambah Akun Baru</span>
                    <div class="grid grid-cols-1 gap-space-sm md:grid-cols-2">
                        <x-ui.modal-field label="Nama Lengkap" name="akun_nama" :required="true" icon="person" :error="$errors->first('name')">
                            <input type="text" id="akun_nama" name="name" value="{{ old('name') }}" maxlength="150" required placeholder="Nama staf"
                                class="w-full rounded-lg bg-card-bg py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Email Login" name="akun_email" :required="true" icon="mail" :error="$errors->first('email')">
                            <input type="email" id="akun_email" name="email" value="{{ old('email') }}" maxlength="150" required placeholder="nama@payroll.test"
                                class="w-full rounded-lg bg-card-bg py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Peran" name="akun_role" :required="true" icon="badge" :error="$errors->first('role')">
                            <select id="akun_role" name="role" required
                                class="w-full appearance-none rounded-lg bg-card-bg py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                                @foreach ($peranTersedia as $opsiPeran)
                                    <option value="{{ $opsiPeran['nilai'] }}" @selected(old('role') === $opsiPeran['nilai'])>{{ $opsiPeran['label'] }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Kata Sandi Awal" name="akun_password" :required="true" icon="lock"
                            hint="Minimal 8 karakter, kombinasi huruf, angka, dan karakter khusus." :error="$errors->first('password')">
                            <input type="text" id="akun_password" name="password" minlength="8" maxlength="100" required placeholder="Contoh: Drv#4829"
                                pattern="(?=.*[A-Za-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                                title="Minimal 8 karakter, kombinasi huruf, angka, dan karakter khusus."
                                class="w-full rounded-lg bg-card-bg py-2.5 pl-9 pr-3 font-mono text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Nomor Telepon / WhatsApp" name="akun_telepon" icon="call" meta="(Opsional)"
                            hint="Diawali angka 8, tanpa angka 0." :error="$errors->first('nomor_telepon')">
                            <span class="pointer-events-none absolute left-9 font-sans text-body-sm font-bold text-slate-text-secondary">+62</span>
                            <input type="tel" id="akun_telepon" name="nomor_telepon" value="{{ \App\Support\FormatNomorTelepon::keLokal(old('nomor_telepon')) }}"
                                inputmode="numeric" maxlength="12" placeholder="81289301123" pattern="8[0-9]{7,11}" title="Diawali angka 8, tanpa angka 0. Contoh: 81289301123" oninput="window.maskTeleponInput(event)"
                                class="w-full rounded-lg bg-card-bg py-2.5 pl-20 pr-3 font-financial-table text-body-md tabular-nums text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>
                    </div>

                    <div class="flex items-center justify-end pt-1">
                        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-primary-container px-5 font-sans text-body-sm font-semibold text-on-primary shadow-md transition-all hover:bg-primary">
                            <span class="material-symbols-outlined text-[1.125rem]">person_add</span>
                            Simpan Akun
                        </button>
                    </div>
                </form>

                {{-- Tabel akun staf --}}
                <div class="overflow-x-auto rounded-xl border border-card-border">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider">
                                <th class="w-12 px-3 py-2.5 text-center font-semibold text-slate-text-secondary">No</th>
                                <th class="px-3 py-2.5 font-semibold text-slate-text-secondary">Nama &amp; Email</th>
                                <th class="px-3 py-2.5 font-semibold text-slate-text-secondary">Peran</th>
                                <th class="px-3 py-2.5 text-center font-semibold text-slate-text-secondary">Dibuat</th>
                                <th class="px-3 py-2.5 text-center font-semibold text-slate-text-secondary">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-card-border font-sans text-financial-table">
                            @forelse ($daftarAkun as $akun)
                                <tr class="hover:bg-surface-container-low/60">
                                    <td class="px-3 py-2.5 text-center tabular-nums text-slate-text-muted">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2.5">
                                        <p class="font-semibold text-slate-text-primary">{{ $akun['nama'] }}</p>
                                        <p class="font-sans text-label-sm text-slate-text-secondary">{{ $akun['email'] }}</p>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-bold {{ $akun['peran'] === 'pimpinan' ? 'bg-primary/10 text-primary' : 'bg-surface-container text-slate-text-primary' }}">
                                            {{ $akun['peran_label'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center text-slate-text-secondary">{{ $akun['dibuat_pada'] }}</td>
                                    <td class="px-3 py-2.5 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button" class="rounded p-1 text-slate-text-secondary hover:bg-surface-container-high hover:text-primary" title="Ubah Akun" @click="bukaEditAkun(@js($akun))">
                                                <span class="material-symbols-outlined text-[1rem]">edit</span>
                                            </button>
                                            <button type="button" class="rounded p-1 text-slate-text-secondary hover:bg-surface-container-high hover:text-danger-red" title="Hapus Akun" @click="bukaHapusAkun(@js($akun))">
                                                <span class="material-symbols-outlined text-[1rem]">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center font-sans text-body-sm text-slate-text-muted">Belum ada akun staf.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <p class="font-sans text-label-sm text-slate-text-muted">
                    Akun Pimpinan hanya dapat dibuat dari portal Pimpinan. Akun Supir dibuat lewat tombol
                    <strong class="text-slate-text-primary">Tambah Driver Baru</strong> agar data karyawan dan akun login konsisten.
                </p>
            </div>

            <div class="flex items-center justify-end bg-canvas-bg px-space-lg py-space-md shadow-inner">
                <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm" @click="modalAkun = false">Tutup</button>
            </div>
        </x-ui.modal-shell>

        {{-- Modal ubah akun --}}
        <x-ui.modal-shell name="modalAkunEdit" title="Ubah Akun Pengguna" icon="edit" max-width="lg" :z-index="70">
            <form method="POST" :action="'{{ url('admin/master/akun') }}/' + akunDipilih.id" class="flex flex-col">
                @csrf
                @method('PATCH')
                <div class="flex flex-col gap-space-md px-space-lg pb-space-md">
                    <x-ui.modal-field label="Nama Lengkap" name="akun_edit_nama" :required="true" icon="person">
                        <input type="text" id="akun_edit_nama" name="name" x-model="akunDipilih.nama" maxlength="150" required
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                    </x-ui.modal-field>

                    <x-ui.modal-field label="Email Login" name="akun_edit_email" :required="true" icon="mail">
                        <input type="email" id="akun_edit_email" name="email" x-model="akunDipilih.email" maxlength="150" required
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                    </x-ui.modal-field>

                    <x-ui.modal-field label="Peran" name="akun_edit_role" :required="true" icon="badge">
                        <select id="akun_edit_role" name="role" x-model="akunDipilih.peran" required
                            class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            @foreach ($peranTersedia as $opsiPeran)
                                <option value="{{ $opsiPeran['nilai'] }}">{{ $opsiPeran['label'] }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                    </x-ui.modal-field>

                    <x-ui.modal-field label="Kata Sandi Baru" name="akun_edit_password" icon="lock" meta="(Kosongkan bila tidak diubah)"
                        hint="Minimal 8 karakter, kombinasi huruf, angka, dan karakter khusus.">
                        <input type="text" id="akun_edit_password" name="password" minlength="8" maxlength="100" placeholder="Contoh: Drv#4829"
                            pattern="(?=.*[A-Za-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                            title="Minimal 8 karakter, kombinasi huruf, angka, dan karakter khusus."
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-mono text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                    </x-ui.modal-field>

                    <x-ui.modal-field label="Nomor Telepon / WhatsApp" name="akun_edit_telepon" icon="call" meta="(Opsional)"
                        hint="Diawali angka 8, tanpa angka 0.">
                        <span class="pointer-events-none absolute left-9 font-sans text-body-sm font-bold text-slate-text-secondary">+62</span>
                        <input type="tel" id="akun_edit_telepon" name="nomor_telepon" x-model="akunDipilih.telepon_lokal"
                            inputmode="numeric" maxlength="12" placeholder="81289301123" pattern="8[0-9]{7,11}" title="Diawali angka 8, tanpa angka 0. Contoh: 81289301123" oninput="window.maskTeleponInput(event)"
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-20 pr-3 font-financial-table text-body-md tabular-nums text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                    </x-ui.modal-field>
                </div>
                <div class="flex items-center justify-between bg-canvas-bg px-space-lg py-space-md shadow-inner">
                    <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm" @click="modalAkunEdit = false">Batal</button>
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-primary-container px-space-xl py-2.5 font-sans text-body-md font-semibold text-on-primary shadow-md hover:bg-primary">
                        <span class="material-symbols-outlined text-[1.125rem]">save</span> Simpan
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- Modal hapus akun --}}
        <x-ui.modal-delete
            name="modalAkunHapus"
            title="Hapus Akun Pengguna?"
            entity="akun"
            action-binding="'{{ url('admin/master/akun') }}/' + akunDipilih.id"
            terkunci-binding="false"
            ringkasan-binding="[
                { label: 'Nama', nilai: akunDipilih.nama },
                { label: 'Email', nilai: akunDipilih.email },
                { label: 'Peran', nilai: akunDipilih.peran_label },
            ]"
            centang="setujuAkunHapus"
            :z-index="70"
        />
    </div>
</x-app-layout>
