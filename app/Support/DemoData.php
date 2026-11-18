<?php

namespace App\Support;

/**
 * Data contoh untuk tahap implementasi UI tanpa database.
 * Seluruh nilai di sini ditampilkan sebagai "data dummy" (penanda merah).
 */
class DemoData
{
    public static function period(): array
    {
        return [
            'code' => 'Jan-26',
            'range' => '21 Agu 2026 — 18 Sep 2026',
            'cut_off' => '19 Sep 2026',
            'work_days' => 28,
            'status' => 'Menunggu ACC Pimpinan',
        ];
    }

    public static function payrollSummary(): array
    {
        return [
            'rit' => 210,
            'tonnage' => 6100,
            'haulage_gross' => 920785810,
            'trip_money' => 438500000,
            'sparepart' => 64620000,
            'cash_required' => 35476580,
            'drivers' => 35,
            'sample_rit' => 82,
            'sample_tonnage' => 2370,
            'sample_net_result' => 164356581,
            'sample_gross_pay' => 16435658,
            'sample_deduction' => 2216000,
            'sample_trip_money' => 171950000,
            'sample_net_pay' => 14219658,
            'warning_count' => 4,
        ];
    }

    public static function payrollRows(): array
    {
        return [
            [
                'initial' => 'KS',
                'name' => 'Kuwat Slamet',
                'plate' => 'BE 8639 AAU',
                'truck' => 'Hino Putih',
                'rit' => 21,
                'tonnage' => '610,00',
                'haulage_gross' => 85616581,
                'potongan_operasional' => 0,
                'omzet' => 85616581,
                'sparepart' => 0,
                'net_result' => 41766581,
                'persentase_komisi_bp' => 1000,
                'persentase_komisi' => 10,
                'commission' => 4176658,
                'gross_pay' => 4176658,
                'tunjangan' => 0,
                'bpjs' => 129000,
                'kasbon' => 500000,
                'deduction' => 629000,
                'deduction_note' => 'BPJS 129rb + Kasbon 500rb',
                'potongan_historis' => 0,
                'penambahan_historis' => 0,
                'trip_money' => 43850000,
                'net_pay' => 3547658,
                'warning' => true,
                'warning_note' => 'Deviasi Tonase > 3%',
                'bank' => 'Mandiri',
                'account' => '1420019283719',
            ],
            [
                'initial' => 'BS',
                'name' => 'Bambang Sugiono',
                'plate' => 'BE 9102 BUA',
                'truck' => 'Hino Hijau',
                'rit' => 19,
                'tonnage' => '542,00',
                'haulage_gross' => 78020000,
                'potongan_operasional' => 0,
                'omzet' => 78020000,
                'sparepart' => 0,
                'net_result' => 38120000,
                'persentase_komisi_bp' => 1000,
                'persentase_komisi' => 10,
                'commission' => 3812000,
                'gross_pay' => 3812000,
                'tunjangan' => 0,
                'bpjs' => 129000,
                'kasbon' => 300000,
                'deduction' => 429000,
                'deduction_note' => 'BPJS 129rb + Kasbon 300rb',
                'potongan_historis' => 0,
                'penambahan_historis' => 0,
                'trip_money' => 39900000,
                'net_pay' => 3383000,
                'warning' => false,
                'warning_note' => null,
                'bank' => 'Mandiri',
                'account' => '1130018274619',
            ],
            [
                'initial' => 'JP',
                'name' => 'Joko Prasetyo',
                'plate' => 'BE 8441 AU',
                'truck' => 'Fuso Orange',
                'rit' => 22,
                'tonnage' => '638,00',
                'haulage_gross' => 91120000,
                'potongan_operasional' => 0,
                'omzet' => 91120000,
                'sparepart' => 0,
                'net_result' => 44920000,
                'persentase_komisi_bp' => 1000,
                'persentase_komisi' => 10,
                'commission' => 4492000,
                'gross_pay' => 4492000,
                'tunjangan' => 0,
                'bpjs' => 129000,
                'kasbon' => 700000,
                'deduction' => 829000,
                'deduction_note' => 'Kasbon 700k + BPJS 129k',
                'potongan_historis' => 0,
                'penambahan_historis' => 0,
                'trip_money' => 46200000,
                'net_pay' => 3663000,
                'warning' => true,
                'warning_note' => 'Kasbon > Gaji Pokok',
                'bank' => 'BRI',
                'account' => '001201992837501',
            ],
            [
                'initial' => 'AS',
                'name' => 'Agus Setiawan',
                'plate' => 'BG 8192 YZ',
                'truck' => 'Hino Putih',
                'rit' => 20,
                'tonnage' => '580,00',
                'haulage_gross' => 81550000,
                'potongan_operasional' => 0,
                'omzet' => 81550000,
                'sparepart' => 0,
                'net_result' => 39550000,
                'persentase_komisi_bp' => 1000,
                'persentase_komisi' => 10,
                'commission' => 3955000,
                'gross_pay' => 3955000,
                'tunjangan' => 0,
                'bpjs' => 129000,
                'kasbon' => 200000,
                'deduction' => 329000,
                'deduction_note' => 'BPJS 129rb + Kasbon 200rb',
                'potongan_historis' => 0,
                'penambahan_historis' => 0,
                'trip_money' => 42000000,
                'net_pay' => 3626000,
                'warning' => false,
                'warning_note' => null,
                'bank' => 'BNI',
                'account' => '0918237461',
            ],
        ];
    }

    public static function payrollTrips(): array
    {
        return [
            ['date' => '18 Sep', 'do' => 'DO-8812', 'route' => 'Pit 3 → Jetty', 'tonnage' => '29,40', 'rate' => 195000, 'warning' => false],
            ['date' => '17 Sep', 'do' => 'DO-8794', 'route' => 'Pit 3 → Jetty', 'tonnage' => '28,90', 'rate' => 195000, 'warning' => false],
            ['date' => '16 Sep', 'do' => 'DO-8740', 'route' => 'Pit Muara → Stockpile', 'tonnage' => '31,20', 'rate' => 210000, 'warning' => true],
            ['date' => '15 Sep', 'do' => 'DO-8702', 'route' => 'Pit 3 → Jetty', 'tonnage' => '29,10', 'rate' => 195000, 'warning' => false],
        ];
    }

    public static function tripSummary(): array
    {
        return [
            'rit' => 21,
            'tonnage' => '610,00',
            'haulage' => 92877797,
            'trip_money' => 43850000,
        ];
    }

    public static function tripRows(): array
    {
        return [
            ['date' => '21/08/2026', 'sj' => 'SJ-MIP-09821', 'driver' => 'Kuwat Slamet', 'plate' => 'BE 8639 AAU', 'truck' => 'Hino 500', 'material' => 'BATU BARA', 'rit' => 1, 'tonnage' => '28,48', 'rate' => 158760, 'total' => 4521485, 'trip_money' => 2250000, 'route' => 'MIP-PORT'],
            ['date' => '22/08/2026', 'sj' => 'SJ-MIP-09855', 'driver' => 'Kuwat Slamet', 'plate' => 'BE 8639 AAU', 'truck' => 'Hino 500', 'material' => 'BATU BARA', 'rit' => 1, 'tonnage' => '29,12', 'rate' => 158760, 'total' => 4623091, 'trip_money' => 2250000, 'route' => 'MIP-PORT'],
            ['date' => '24/08/2026', 'sj' => 'SJ-MIP-09904', 'driver' => 'Kuwat Slamet', 'plate' => 'BE 8639 AAU', 'truck' => 'Hino 500', 'material' => 'BATU BARA', 'rit' => 1, 'tonnage' => '27,90', 'rate' => 158760, 'total' => 4429404, 'trip_money' => 2250000, 'route' => 'MIP-36'],
            ['date' => '26/08/2026', 'sj' => 'SJ-MIP-09942', 'driver' => 'Kuwat Slamet', 'plate' => 'BE 8639 AAU', 'truck' => 'Hino 500', 'material' => 'BATU BARA', 'rit' => 1, 'tonnage' => '28,85', 'rate' => 158760, 'total' => 4580226, 'trip_money' => 2250000, 'route' => 'MIP-PORT'],
            ['date' => '28/08/2026', 'sj' => 'SJ-MIP-09988', 'driver' => 'Kuwat Slamet', 'plate' => 'BE 8639 AAU', 'truck' => 'Hino 500', 'material' => 'BATU BARA', 'rit' => 1, 'tonnage' => '29,64', 'rate' => 158760, 'total' => 4705646, 'trip_money' => 2250000, 'route' => 'MIP-PORT'],
            ['date' => '30/08/2026', 'sj' => 'SJ-MIP-10012', 'driver' => 'Kuwat Slamet', 'plate' => 'BE 8639 AAU', 'truck' => 'Hino 500', 'material' => 'BATU BARA', 'rit' => 1, 'tonnage' => '28,20', 'rate' => 158760, 'total' => 4477032, 'trip_money' => 2250000, 'route' => 'MIP-PORT'],
        ];
    }

    public static function loanSummary(): array
    {
        return [
            'outstanding' => 12500000,
            'active_drivers' => 14,
            'installment' => 5960000,
            'bpjs' => 4515000,
            'allowance' => 1850000,
            'allowance_drivers' => 18,
        ];
    }

    public static function loans(): array
    {
        return [
            ['initial' => 'KS', 'name' => 'Kuwat Slamet', 'nip' => 'DRV-KAFA-042', 'plate' => 'BE 8639 AAU', 'truck' => 'Hino', 'date' => '12 Jan 2026', 'purpose' => 'Perbaikan Rumah', 'principal' => 2000000, 'tenor' => 4, 'remaining' => 1000000, 'remaining_pct' => 50, 'installment' => 500000, 'installment_no' => 'Angsuran ke-2/4', 'bpjs' => 129000, 'total_deduction' => 629000, 'status' => 'Lancar', 'status_tone' => 'emerald'],
            ['initial' => 'BS', 'name' => 'Bambang Sugiono', 'nip' => 'DRV-KAFA-018', 'plate' => 'BG 8190 NX', 'truck' => 'Fuso', 'date' => '05 Okt 2025', 'purpose' => 'Biaya Masuk Kuliah Anak', 'principal' => 3000000, 'tenor' => 4, 'remaining' => 750000, 'remaining_pct' => 75, 'installment' => 750000, 'installment_no' => 'Ke-4 (Final)', 'bpjs' => 129000, 'total_deduction' => 879000, 'status' => 'Lunas Jan-26', 'status_tone' => 'emerald'],
            ['initial' => 'JP', 'name' => 'Joko Prasetyo', 'nip' => 'DRV-KAFA-029', 'plate' => 'BE 9012 BZ', 'truck' => 'Hino', 'date' => '02 Des 2025', 'purpose' => 'Ganti Ban Cadangan', 'principal' => 1500000, 'tenor' => 3, 'remaining' => 500000, 'remaining_pct' => 33, 'installment' => 500000, 'installment_no' => 'Angsuran ke-3/3', 'bpjs' => 129000, 'total_deduction' => 629000, 'status' => 'Lancar', 'status_tone' => 'emerald'],
            ['initial' => 'AS', 'name' => 'Agus Setiawan', 'nip' => 'DRV-KAFA-007', 'plate' => 'BG 8711 KL', 'truck' => 'Isuzu Giga', 'date' => '18 Nov 2025', 'purpose' => 'Renovasi Dapur', 'principal' => 2500000, 'tenor' => 4, 'remaining' => 1250000, 'remaining_pct' => 50, 'installment' => 625000, 'installment_no' => 'Angsuran ke-2/4', 'bpjs' => 129000, 'total_deduction' => 754000, 'status' => 'Mendekati Batas', 'status_tone' => 'amber'],
            ['initial' => 'SR', 'name' => 'Slamet Riyadi', 'nip' => 'DRV-KAFA-051', 'plate' => 'BE 8119 YU', 'truck' => 'Hino', 'date' => '20 Jan 2026', 'purpose' => 'Biaya Pengobatan Keluarga', 'principal' => 1000000, 'tenor' => 2, 'remaining' => 1000000, 'remaining_pct' => 100, 'installment' => 500000, 'installment_no' => 'Angsuran ke-1/2', 'bpjs' => 0, 'total_deduction' => 500000, 'status' => 'Baru', 'status_tone' => 'blue'],
        ];
    }

    public static function employees(): array
    {
        return [
            ['initial' => 'KS', 'name' => 'Kuwat Slamet', 'nip' => 'DRV-KAFA-042', 'phone' => '0812-8930-1123', 'plate' => 'BE 8639 AAU', 'truck' => 'Hino 500 TI Putih', 'bank' => 'BCA', 'account' => '8271928391', 'verified' => true, 'commission' => '10% Bersih', 'bpjs' => 129000, 'limit' => 3000000, 'remaining' => 1500000, 'status' => 'Aktif', 'tone' => 'emerald'],
            ['initial' => 'BS', 'name' => 'Bambang Sugiono', 'nip' => 'DRV-KAFA-018', 'phone' => '0852-1922-8371', 'plate' => 'BG 8190 NX', 'truck' => 'Fuso Fighter 220PS', 'bank' => 'MANDIRI', 'account' => '1130018274619', 'verified' => true, 'commission' => '10% Bersih', 'bpjs' => 129000, 'limit' => 3000000, 'remaining' => 0, 'status' => 'Aktif', 'tone' => 'emerald'],
            ['initial' => 'JP', 'name' => 'Joko Prasetyo', 'nip' => 'DRV-KAFA-029', 'phone' => '0813-9081-3774', 'plate' => 'BE 9012 BZ', 'truck' => 'Hino 500 TI Hijau (Subkon Surya)', 'bank' => 'BRI', 'account' => '001201992837501', 'verified' => true, 'commission' => '9.5% Mandiri', 'bpjs' => 129000, 'limit' => 2500000, 'remaining' => 800000, 'status' => 'Izin Sakit', 'tone' => 'amber'],
            ['initial' => 'AS', 'name' => 'Agus Setiawan', 'nip' => 'DRV-KAFA-007', 'phone' => '0821-7788-9900', 'plate' => 'BG 8711 KL', 'truck' => 'Isuzu Giga FVZ', 'bank' => 'BNI', 'account' => '0918237461', 'verified' => true, 'commission' => '10% Bersih', 'bpjs' => 129000, 'limit' => 3000000, 'remaining' => 1200000, 'status' => 'Aktif', 'tone' => 'emerald'],
            ['initial' => 'SR', 'name' => 'Slamet Riyadi', 'nip' => 'DRV-KAFA-051', 'phone' => '0877-3344-1122', 'plate' => 'BE 8119 YU', 'truck' => 'Hino 500 TI Putih', 'bank' => 'BNI', 'account' => '0918237461', 'verified' => false, 'commission' => '8.0% Magang', 'bpjs' => 0, 'limit' => 1000000, 'remaining' => 0, 'status' => 'Probation', 'tone' => 'amber'],
        ];
    }

    public static function masterSummary(): array
    {
        return [
            'drivers' => 35,
            'drivers_ready' => 32,
            'drivers_leave' => 3,
            'trucks' => 42,
            'trucks_ready' => 38,
            'trucks_kafa' => 28,
            'trucks_subcon' => 14,
            'routes' => 4,
        ];
    }

    public static function routes(): array
    {
        return [
            ['code' => 'MIP-PORT', 'name' => 'Pit Muara Enim → Jetty Port', 'rate' => 158760, 'trip_money' => 2250000],
            ['code' => 'MIP-36', 'name' => 'Pit Muara Enim → KM 36 Stockpile', 'rate' => 119880, 'trip_money' => 1800000],
            ['code' => 'TAL-PORT', 'name' => 'Tambang Air Laya → Pelabuhan', 'rate' => 164500, 'trip_money' => 2400000],
        ];
    }

    public static function truckComposition(): array
    {
        return [
            ['model' => 'Hino 500 TI', 'unit' => 30, 'pct' => 71.4],
            ['model' => 'Fuso Fighter 220PS', 'unit' => 8, 'pct' => 19.0],
            ['model' => 'Isuzu Giga Heavy Duty', 'unit' => 4, 'pct' => 9.6],
        ];
    }

    /**
     * ---------------------------------------------------------------------
     * Dataset Pimpinan / Eksekutif
     * ---------------------------------------------------------------------
     */
    public static function executiveSummary(): array
    {
        return [
            'omzet' => 920785810,
            'omzet_kotor' => 921585026,
            'potongan_operasional' => 799216,
            'hasil_bersih_armada' => 417665810,
            'commission' => 41766581,
            'tunjangan' => 0,
            'bpjs' => 4515000,
            'kasbon' => 5960000,
            'deduction' => 10475000,
            'potongan_historis' => 0,
            'penambahan_historis' => 0,
            'omzet_growth' => '+14.2%',
            'tonnage' => '6.100,00',
            'rit' => 210,
            'cash_out' => 503120000,
            'trip_money' => 438500000,
            'sparepart' => 64620000,
            'margin' => 386374229,
            'margin_pct' => '42.0%',
            'net_pay' => 31291581,
            'drivers' => 35,
            'pending_acc' => 4,
            'audit_ready' => 31,
        ];
    }

    public static function executiveTrend(): array
    {
        return [
            ['period' => 'Agu-25', 'omzet' => 'Rp 612M', 'tonnage' => '4.120 T', 'points' => [40, 120]],
            ['period' => 'Sep-25', 'omzet' => 'Rp 715M', 'tonnage' => '4.890 T', 'points' => [160, 95]],
            ['period' => 'Okt-25', 'omzet' => 'Rp 680M', 'tonnage' => '4.610 T', 'points' => [280, 105]],
            ['period' => 'Nov-25', 'omzet' => 'Rp 789M', 'tonnage' => '5.340 T', 'points' => [400, 70]],
            ['period' => 'Des-25', 'omzet' => 'Rp 806M', 'tonnage' => '5.450 T', 'points' => [520, 50]],
            ['period' => 'Jan-26', 'omzet' => 'Rp 921M', 'tonnage' => '6.100 T', 'points' => [640, 25]],
        ];
    }

    public static function executiveRouteBreakdown(): array
    {
        return [
            ['pct' => '72%', 'route' => 'MIP → Stockpile Jetty', 'omzet' => 662965783, 'detail' => '4.392 Ton • 151 Rit', 'tone' => 'indigo'],
            ['pct' => '18%', 'route' => 'MIP → KM 36 Intermediate', 'omzet' => 165741445, 'detail' => '1.098 Ton • 38 Rit', 'tone' => 'sky'],
            ['pct' => '10%', 'route' => 'TAL → Stockpile Jetty', 'omzet' => 92078582, 'detail' => '610 Ton • 21 Rit', 'tone' => 'slate'],
        ];
    }

    public static function costComposition(): array
    {
        return [
            'total' => 503120000,
            'trip_money' => 438500000,
            'trip_money_pct' => 87.2,
            'sparepart' => 64620000,
            'sparepart_pct' => 12.8,
        ];
    }

    public static function approvalQueue(): array
    {
        return [
            [
                'initial' => 'KS', 'name' => 'Kuwat Slamet', 'plate' => 'BE 8639 AAU',
                'truck' => 'Hino 500', 'rit' => 21, 'tonnage' => '610,00',
                'omzet' => 92877797, 'trip_money' => 43850000, 'sparepart' => 6462000,
                'sparepart_note' => 'Beban Ban & Transmisi', 'commission' => 4176658,
                'deduction' => 629000, 'net_pay' => 3547658,
                'status' => 'Perlu Review Direksi', 'tone' => 'amber', 'anomaly' => true,
            ],
            [
                'initial' => 'BM', 'name' => 'Bambang Mulyono', 'plate' => 'BG 8241 UT',
                'truck' => 'Fuso Fighter', 'rit' => 24, 'tonnage' => '698,50',
                'omzet' => 106240000, 'trip_money' => 50400000, 'sparepart' => 1250000,
                'sparepart_note' => 'Ganti Oli Rutin', 'commission' => 4780000,
                'deduction' => 500000, 'net_pay' => 4280000,
                'status' => 'Rekomendasi ACC', 'tone' => 'emerald', 'anomaly' => false,
            ],
            [
                'initial' => 'DP', 'name' => 'Dedi Prasetyo', 'plate' => 'BE 9012 BZ',
                'truck' => 'Quester CKE', 'rit' => 19, 'tonnage' => '551,20',
                'omzet' => 83824000, 'trip_money' => 39900000, 'sparepart' => 4850000,
                'sparepart_note' => 'Beban Klep & Kopling', 'commission' => 3772000,
                'deduction' => 850000, 'net_pay' => 2922000,
                'status' => 'Perlu Review Direksi', 'tone' => 'amber', 'anomaly' => true,
            ],
            [
                'initial' => 'SH', 'name' => 'Suryadi Hermanto', 'plate' => 'BG 8819 LM',
                'truck' => 'Hino 500 SG', 'rit' => 22, 'tonnage' => '642,80',
                'omzet' => 97770000, 'trip_money' => 46200000, 'sparepart' => 850000,
                'sparepart_note' => 'Servis Ringan', 'commission' => 4399000,
                'deduction' => 400000, 'net_pay' => 3999000,
                'status' => 'Rekomendasi ACC', 'tone' => 'emerald', 'anomaly' => false,
            ],
        ];
    }

    public static function executivePayrollReview(): array
    {
        return [
            [
                'id' => 1,
                'initial' => 'KS', 'name' => 'Kuwat Slamet', 'nip' => 'DRV-KAFA-042', 'plate' => 'BE 8639 AAU',
                'truck' => 'Hino 500 Putih', 'bank' => 'BCA', 'account' => '8271928391',
                'rit' => 21, 'tonnage' => '610,00',
                'omzet_kotor' => 92877797, 'potongan_operasional' => 799216, 'omzet' => 92078581,
                'trip_money' => 43850000, 'sparepart' => 6462000, 'hasil_bersih_armada' => 41766581,
                'commission' => 4176658, 'tunjangan' => 0, 'bpjs' => 129000, 'kasbon' => 500000,
                'deduction' => 629000, 'potongan_historis' => 0, 'penambahan_historis' => 0,
                'net_pay' => 3547658,
                'status' => 'Perlu Review', 'tone' => 'amber', 'anomaly' => true,
                'note' => 'Beban bengkel Rp 6.462.000 telah diverifikasi Kepala Bengkel (Bpk. Yudi) pada 19 Jan 2026 akibat kerusakan kampas mendadak di tanjakan Tebing Enim. Nota fisik terlampir valid dan disetujui pembebanannya ke operasional armada periode ini.',
                'sparepart_detail' => 'Penggantian 2 Ban Vulkanisir (Rp 3.800.000) + Kampas Kopling Set (Rp 2.662.000)',
            ],
            [
                'id' => 2,
                'initial' => 'JP', 'name' => 'Joko Purwanto', 'nip' => 'DRV-KAFA-029', 'plate' => 'BE 8441 AU',
                'truck' => 'Fuso Orange', 'bank' => 'BRI', 'account' => '001201992837501',
                'rit' => 22, 'tonnage' => '638,00',
                'omzet_kotor' => 93220000, 'potongan_operasional' => 0, 'omzet' => 93220000,
                'trip_money' => 46200000, 'sparepart' => 2100000, 'hasil_bersih_armada' => 44920000,
                'commission' => 4492000, 'tunjangan' => 0, 'bpjs' => 129000, 'kasbon' => 700000,
                'deduction' => 829000, 'potongan_historis' => 0, 'penambahan_historis' => 0,
                'net_pay' => 3663000,
                'status' => 'Siap ACC', 'tone' => 'emerald', 'anomaly' => false,
                'note' => 'Tidak ada anomali. Ritase dan uang jalan sesuai surat jalan yang divalidasi checker Posko Muara Enim.',
                'sparepart_detail' => 'Servis berkala 10.000 KM (Rp 2.100.000)',
            ],
            [
                'id' => 3,
                'initial' => 'AS', 'name' => 'Agus Setiawan', 'nip' => 'DRV-KAFA-007', 'plate' => 'BG 8192 YZ',
                'truck' => 'Hino Putih', 'bank' => 'BNI', 'account' => '0918237461',
                'rit' => 20, 'tonnage' => '580,00',
                'omzet_kotor' => 83000000, 'potongan_operasional' => 0, 'omzet' => 83000000,
                'trip_money' => 42000000, 'sparepart' => 1450000, 'hasil_bersih_armada' => 39550000,
                'commission' => 3955000, 'tunjangan' => 0, 'bpjs' => 129000, 'kasbon' => 200000,
                'deduction' => 329000, 'potongan_historis' => 0, 'penambahan_historis' => 0,
                'net_pay' => 3626000,
                'status' => 'Siap ACC', 'tone' => 'emerald', 'anomaly' => false,
                'note' => 'Rekonsiliasi bersih. Tonase timbangan sesuai dengan surat jalan digital dan manifest harian.',
                'sparepart_detail' => 'Ganti filter udara & oli (Rp 1.450.000)',
            ],
            [
                'id' => 4,
                'initial' => 'BS', 'name' => 'Bambang Sudarsono', 'nip' => 'DRV-KAFA-018', 'plate' => 'BG 8190 NX',
                'truck' => 'Fuso Fighter', 'bank' => 'MANDIRI', 'account' => '1130018274619',
                'rit' => 19, 'tonnage' => '542,00',
                'omzet_kotor' => 83140000, 'potongan_operasional' => 0, 'omzet' => 83140000,
                'trip_money' => 39900000, 'sparepart' => 5120000, 'hasil_bersih_armada' => 38120000,
                'commission' => 3812000, 'tunjangan' => 0, 'bpjs' => 129000, 'kasbon' => 300000,
                'deduction' => 429000, 'potongan_historis' => 0, 'penambahan_historis' => 0,
                'net_pay' => 3383000,
                'status' => 'Perlu Review', 'tone' => 'amber', 'anomaly' => true,
                'note' => 'Beban sparepart melebihi toleransi ritase. Perlu konfirmasi ulang nota bengkel sebelum otorisasi.',
                'sparepart_detail' => 'Overhaul kopling & rem angin (Rp 5.120.000)',
            ],
        ];
    }

    public static function financialReport(): array
    {
        return [
            'doc_id' => '#EXP-2026-0819-FIN',
            'realisasi' => 35476580,
            'omzet_bersih' => 920785810,
            'trip_money' => 438500000,
            'sparepart' => 64620000,
            'kasbon_outstanding' => 12500000,
            'kasbon_installment' => 5960000,
            'kasbon_pct' => 48,
            'return_ratio' => '94.2%',
            'cash_reserve' => 'Rp 550 Jt',
        ];
    }


    public static function financialRows(): array
    {
        return [
            ['name' => 'Supriadi Santoso', 'nip' => 'DRV-0021', 'plate' => 'KT 8821 FE', 'rit' => 8, 'tonnage' => '232,5', 'omzet' => 35100000, 'trip_money' => 16700000, 'sparepart' => 2450000, 'gross' => 3510000, 'bpjs' => 150000, 'kasbon' => 500000, 'net' => 2860000, 'bank' => 'BCA', 'account' => '78102998', 'acc' => 'ACC 19/01'],
            ['name' => 'Bambang Haryanto', 'nip' => 'DRV-0014', 'plate' => 'KT 8140 CD', 'rit' => 9, 'tonnage' => '261,0', 'omzet' => 39400000, 'trip_money' => 18800000, 'sparepart' => 3100000, 'gross' => 3940000, 'bpjs' => 150000, 'kasbon' => 400000, 'net' => 3390000, 'bank' => 'MANDIRI', 'account' => '11300199', 'acc' => 'ACC 19/01'],
            ['name' => 'Kuwat Slamet', 'nip' => 'DRV-KAFA-042', 'plate' => 'BE 8639 AAU', 'rit' => 21, 'tonnage' => '610,0', 'omzet' => 92877797, 'trip_money' => 43850000, 'sparepart' => 6462000, 'gross' => 4176658, 'bpjs' => 129000, 'kasbon' => 500000, 'net' => 3547658, 'bank' => 'BCA', 'account' => '8271928391', 'acc' => 'Pending'],
            ['name' => 'Joko Prasetyo', 'nip' => 'DRV-KAFA-029', 'plate' => 'BE 9012 BZ', 'rit' => 19, 'tonnage' => '551,2', 'omzet' => 83824000, 'trip_money' => 39900000, 'sparepart' => 4850000, 'gross' => 3772000, 'bpjs' => 129000, 'kasbon' => 850000, 'net' => 2922000, 'bank' => 'BRI', 'account' => '00120199', 'acc' => 'Pending'],
            ['name' => 'Suryadi Hermanto', 'nip' => 'DRV-KAFA-051', 'plate' => 'BG 8819 LM', 'rit' => 22, 'tonnage' => '642,8', 'omzet' => 97770000, 'trip_money' => 46200000, 'sparepart' => 850000, 'gross' => 4399000, 'bpjs' => 129000, 'kasbon' => 400000, 'net' => 3999000, 'bank' => 'BNI', 'account' => '09182374', 'acc' => 'ACC 19/01'],
        ];
    }

    /**
     * ---------------------------------------------------------------------
     * Dataset Portal Supir
     * ---------------------------------------------------------------------
     */
    public static function driverProfile(): array
    {
        return [
            'name' => 'Kuwat Slamet',
            'nip' => 'DRV-KAFA-042',
            'initial' => 'KS',
            'phone' => '0812-8930-1123',
            'email' => 'kuwat.slamet@kafa-logistics.id',
            'plate' => 'BE 8639 AAU',
            'truck' => 'Hino 500 TI Super Ranger',
            'truck_note' => 'Dump Truck 10 Roda • Tronton 30 Ton',
            'route' => 'Pit Muara Enim → Port Jetty Dermaga Kertapati',
            'pool' => 'Muara Enim Central',
            'bank' => 'BCA',
            'account' => '8271928391',
            'npwp' => '72.819.203.4-311.000',
            'bpjs' => 'BPJS Ketenagakerjaan • JKK & JKM Aktif',
            'scheme' => 'Komisi 10% Bersih',
            'rit_total' => 418,
            'tenure' => '2.4',
            'hse' => '4.9',
        ];
    }

    public static function driverPayroll(): array
    {
        return [
            'gross_omzet' => 92877797,
            'retribution' => 799216,
            'net_revenue' => 92078581,
            'trip_money' => 43850000,
            'sparepart' => 6462000,
            'unit_net' => 41766581,
            'commission_pct' => 10,
            'commission' => 4176658,
            'bpjs' => 129000,
            'kasbon' => 500000,
            'net_pay' => 3547658,
            'rit' => 21,
            'tonnage' => '610,00',
            'kasbon_remaining' => 1000000,
            'approved_at' => '19 Jan 2026, 09:15 WIB',
            'hash' => '9F8A-21E0-44BC',
            'audit_id' => 'ACC-JAN26-DRV042-998',
            'approver' => 'Ir. Hendra Wijaya, M.M.',
            'approver_role' => 'Direktur Operasional KAFA Hauling',
        ];
    }

    public static function driverTrips(): array
    {
        return [
            ['date' => '18 Sep 2026', 'sj' => 'SJ-MIP-10012', 'route' => 'MIP-PORT', 'tonnage' => '28,20', 'rate' => 158760, 'trip_money' => 2250000, 'status' => 'Terverifikasi', 'tone' => 'emerald'],
            ['date' => '17 Sep 2026', 'sj' => 'SJ-MIP-09988', 'route' => 'MIP-PORT', 'tonnage' => '29,64', 'rate' => 158760, 'trip_money' => 2250000, 'status' => 'Terverifikasi', 'tone' => 'emerald'],
            ['date' => '16 Sep 2026', 'sj' => 'SJ-MIP-09942', 'route' => 'MIP-PORT', 'tonnage' => '28,85', 'rate' => 158760, 'trip_money' => 2250000, 'status' => 'Terverifikasi', 'tone' => 'emerald'],
            ['date' => '15 Sep 2026', 'sj' => 'SJ-MIP-09904', 'route' => 'MIP-36', 'tonnage' => '27,90', 'rate' => 119880, 'trip_money' => 1800000, 'status' => 'Terverifikasi', 'tone' => 'emerald'],
            ['date' => '13 Sep 2026', 'sj' => 'SJ-MIP-09855', 'route' => 'MIP-PORT', 'tonnage' => '29,12', 'rate' => 158760, 'trip_money' => 2250000, 'status' => 'Terverifikasi', 'tone' => 'emerald'],
            ['date' => '12 Sep 2026', 'sj' => 'SJ-MIP-09821', 'route' => 'MIP-PORT', 'tonnage' => '28,48', 'rate' => 158760, 'trip_money' => 2250000, 'status' => 'Terverifikasi', 'tone' => 'emerald'],
            ['date' => '11 Sep 2026', 'sj' => 'SJ-MIP-09790', 'route' => 'TAL-PORT', 'tonnage' => '31,20', 'rate' => 164500, 'trip_money' => 2400000, 'status' => 'Perlu Klarifikasi', 'tone' => 'amber'],
        ];
    }

    public static function driverLoan(): array
    {
        return [
            'principal' => 2000000,
            'remaining' => 1000000,
            'progress_pct' => 50,
            'installment' => 500000,
            'tenor_total' => 4,
            'tenor_paid' => 2,
            'tenor_left' => 2,
            'purpose' => 'Renovasi Rumah',
            'next_cut_off' => '25 Jan 2026',
        ];
    }

    public static function driverLoanMutations(): array
    {
        return [
            ['date' => '25 Jan 2026', 'type_date' => 'Cut-Off Payroll', 'code' => 'POT-2026-01-042', 'kind' => 'Potongan Payroll Otomatis', 'purpose' => 'Angsuran Ke-2 Renovasi Rumah (Slip Jan-26)', 'debit' => 500000, 'credit' => 0, 'balance' => 1000000, 'status' => 'TERPOTONG SLIP GAJI', 'tone' => 'amber', 'filter' => 'potongan'],
            ['date' => '25 Des 2025', 'type_date' => 'Cut-Off Payroll', 'code' => 'POT-2025-12-042', 'kind' => 'Potongan Payroll Otomatis', 'purpose' => 'Angsuran Ke-1 Renovasi Rumah (Slip Des-25)', 'debit' => 500000, 'credit' => 0, 'balance' => 1500000, 'status' => 'SELESAI 100%', 'tone' => 'emerald', 'filter' => 'potongan'],
            ['date' => '12 Des 2025', 'type_date' => 'Kasir Tunai / TF', 'code' => 'CR-2025-12-088', 'kind' => 'Pencairan Kasir PT KAFA', 'purpose' => 'Perbaikan Atap Rumah Bocor Akibat Badai', 'debit' => 0, 'credit' => 2000000, 'balance' => 2000000, 'status' => 'DICAIRKAN', 'tone' => 'blue', 'filter' => 'pencairan'],
            ['date' => '20 Okt 2025', 'type_date' => 'Cut-Off Payroll', 'code' => 'POT-2025-10-042', 'kind' => 'Pelunasan Kasbon Lama', 'purpose' => 'Pelunasan Pinjaman Pendidikan Anak', 'debit' => 750000, 'credit' => 0, 'balance' => 0, 'status' => 'LUNAS', 'tone' => 'emerald', 'filter' => 'potongan'],
            ['date' => '02 Sep 2025', 'type_date' => 'Kasir Tunai', 'code' => 'CR-2025-09-014', 'kind' => 'Pinjaman Darurat', 'purpose' => 'Biaya Berobat Keluarga di RS Muara Enim', 'debit' => 0, 'credit' => 750000, 'balance' => 750000, 'status' => 'LUNAS', 'tone' => 'slate', 'filter' => 'pencairan'],
        ];
    }

    public static function driverLoanSchedule(): array
    {
        return [
            ['period' => 'DES 2025', 'state' => 'LUNAS', 'amount' => 500000, 'remain' => 'Sisa: Rp 1.500.000', 'tone' => 'emerald'],
            ['period' => 'JAN 2026', 'state' => 'AKAN POTONG', 'amount' => 500000, 'remain' => 'Sisa: Rp 1.000.000', 'tone' => 'amber'],
            ['period' => 'FEB 2026', 'state' => 'ANGSURAN 3', 'amount' => 500000, 'remain' => 'Sisa: Rp 500.000', 'tone' => 'slate'],
            ['period' => 'MAR 2026', 'state' => 'PELUNASAN', 'amount' => 500000, 'remain' => 'Saldo Akhir: Rp 0', 'tone' => 'primary'],
        ];
    }

    public static function driverTruckCompliance(): array
    {
        return [
            ['label' => 'Uji Kelayakan KIR Dishub', 'note' => 'No. Uji: PLG-649201', 'badge' => 'Sisa 9 Hari', 'tone' => 'amber', 'footer_label' => 'Masa Berlaku:', 'footer' => '28 Sep 2026 (Segera Perpanjang)', 'icon' => 'fact_check'],
            ['label' => 'Pajak STNK & TNKB', 'note' => 'Samsat Muara Enim', 'badge' => 'Aktif', 'tone' => 'emerald', 'footer_label' => 'Valid s/d:', 'footer' => '14 Jan 2027', 'icon' => 'assignment_turned_in'],
            ['label' => 'Odometer & Servis Berkala', 'note' => 'Servis Terakhir: Pool Lahat', 'badge' => '142.350 KM', 'tone' => 'slate', 'footer_label' => 'Item Penggantian:', 'footer' => 'Oli Mesin + Kampas Rem Baru', 'icon' => 'build_circle'],
            ['label' => 'Voucher Bahan Bakar (B35)', 'note' => 'Dispenser SPBU Tambang MIP-01', 'badge' => '#VCR-9921', 'tone' => 'slate', 'footer_label' => 'Alokasi Rit:', 'footer' => '120 Liter / Putaran Rit', 'icon' => 'local_gas_station'],
        ];
    }

    public static function mineCompliance(): array
    {
        return [
            ['label' => 'Lisensi Korlantas', 'title' => 'SIM BII Umum', 'note' => 'Tronton, Trailer & Kereta Tempelan', 'footer_label' => 'Masa Berlaku:', 'footer' => 's/d 12 Des 2027', 'tone' => 'primary', 'icon' => 'drive_eta'],
            ['label' => 'HSE Academy', 'title' => 'Defensive Driving (DDT)', 'note' => 'Sertifikasi Keselamatan PT MIP HSE', 'footer_label' => 'Status Sertifikat:', 'footer' => 'Terakreditasi Kelas A', 'tone' => 'emerald', 'icon' => 'military_tech'],
            ['label' => 'Izin Tambang (SIMPER)', 'title' => 'Level 2 Hauling Track', 'note' => 'Otorisasi Pit Aktif & Jalur Hauling', 'footer_label' => 'Validasi RFID Tambang:', 'footer' => 'Aktif (Scan Gate MIP 01)', 'tone' => 'amber', 'icon' => 'card_membership'],
        ];
    }

    /**
     * 35 sel kalender: 31 Agu 2026 s/d 4 Okt 2026.
     * type: hauling | warning | audit | pay | maintenance | danger
     */
    public static function calendarDays(): array
    {
        $days = [];

        $hauling = ['label' => '24 Rit / 680 Ton', 'type' => 'hauling'];

        $days[] = ['day' => 31, 'muted' => true, 'events' => []];

        for ($d = 1; $d <= 30; $d++) {
            $events = [];
            $muted = false;

            if ($d <= 18) {
                $events[] = $hauling;
            }

            if ($d === 6) {
                $events[] = ['label' => 'Service Berkala', 'type' => 'maintenance'];
            }

            if ($d === 12) {
                $events = [['label' => 'Service Berkala', 'type' => 'maintenance']];
            }

            if ($d === 19) {
                $events = [
                    ['label' => 'CUT-OFF 23:59', 'type' => 'warning'],
                    ['label' => 'Validasi SJ Fisik', 'type' => 'danger'],
                ];
            }

            if ($d === 20) {
                $events = [['label' => 'Batch Computation', 'type' => 'audit']];
            }

            if ($d === 21) {
                $events = [['label' => 'Audit HRD', 'type' => 'audit']];
            }

            if ($d === 22) {
                $events = [['label' => 'ACC Pimpinan', 'type' => 'audit']];
            }

            if ($d === 23) {
                $events = [['label' => 'KIR BE 8639 AAU', 'type' => 'danger']];
            }

            if ($d === 25) {
                $events = [['label' => 'PAY DAY', 'type' => 'pay']];
            }

            if ($d === 30) {
                $events[] = ['label' => 'Rekonsiliasi BBM', 'type' => 'warning'];
            }

            $days[] = ['day' => $d, 'muted' => $muted, 'events' => $events];
        }

        for ($d = 1; $d <= 4; $d++) {
            $days[] = ['day' => $d, 'muted' => true, 'events' => []];
        }

        return $days;
    }
}
