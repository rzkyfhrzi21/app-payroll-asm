{{--
    EMAIL: PEMBERITAHUAN SLIP GAJI SUDAH DICAIRKAN

    Analogi Sederhana:
    Surat elektronik resmi berisi ringkasan gaji supir yang baru saja ditransfer.
    Sengaja memakai HTML sederhana tanpa dependensi agar tampil rapi di Gmail,
    Outlook, maupun aplikasi mail HP.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Slip Gaji {{ $rincian['periode'] }} Sudah Dicairkan</title>
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="background:#1e293b;padding:20px 24px;color:#ffffff;">
                <div style="font-size:16px;font-weight:bold;letter-spacing:.02em;">{{ $rincian['nama_perusahaan'] }}</div>
                <div style="font-size:12px;color:#cbd5e1;margin-top:4px;">Pemberitahuan Pencairan Gaji Supir</div>
            </td>
        </tr>

        <tr>
            <td style="padding:24px;">
                <p style="margin:0 0 12px;font-size:14px;">Halo <strong>{{ $rincian['nama'] }}</strong> ({{ $rincian['nip'] }}),</p>
                <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
                    Gaji Anda untuk periode <strong>{{ $rincian['periode'] }}</strong>
                    ({{ $rincian['rentang'] }}) sudah <strong>dicairkan</strong> pada
                    {{ $rincian['tanggal_cair'] }}.
                </p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
                    <tr>
                        <td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Total Ritase</td>
                        <td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:bold;">{{ $rincian['rit'] }} Rit</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Total Tonase</td>
                        <td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:bold;">{{ $rincian['tonase'] }} Ton</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Potongan BPJS</td>
                        <td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;">Rp {{ number_format($rincian['potongan_bpjs'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Potongan Kasbon</td>
                        <td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;">Rp {{ number_format($rincian['potongan_kasbon'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;color:#0f172a;font-weight:bold;">Gaji Bersih Diterima</td>
                        <td style="padding:12px 0;text-align:right;font-weight:bold;font-size:16px;color:#047857;">
                            Rp {{ number_format($rincian['gaji_bersih'], 0, ',', '.') }}
                        </td>
                    </tr>
                </table>

                <p style="margin:16px 0 0;font-size:13px;color:#475569;">
                    Transfer ke rekening <strong>{{ $rincian['bank'] }} &bull; {{ $rincian['nomor_rekening'] }}</strong>.
                </p>

                <p style="margin:20px 0 0;">
                    <a href="{{ url('/my/payroll') }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:8px;font-size:13px;font-weight:bold;">
                        Lihat Slip Gaji di Portal Supir
                    </a>
                </p>
            </td>
        </tr>

        <tr>
            <td style="padding:16px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:11px;color:#64748b;">
                Email ini dikirim otomatis oleh sistem penggajian {{ $rincian['nama_perusahaan'] }}. Mohon tidak membalas email ini.
            </td>
        </tr>
    </table>
</body>
</html>
