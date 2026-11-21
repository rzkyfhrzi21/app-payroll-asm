<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Regresi dropdown aksi tabel:
 * 1. Item dropdown wajib menghasilkan handler @click yang valid (bukan @js() mentah).
 * 2. Setiap modal wajib berada di dalam scope Alpine pemiliknya, agar x-show-nya bekerja.
 * 3. Markup tabel tidak boleh kehilangan penutup tag.
 */
class DropdownAksiTabelTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    /**
     * @return array<string, string> nama halaman => URL
     */
    private function halamanTabel(): array
    {
        return [
            'supir' => '/admin/master/employees',
            'truk' => '/admin/master/trucks',
            'rute' => '/admin/master/routes',
            'surat jalan' => '/admin/operational/trips',
            'biaya' => '/admin/operational/expenses',
            'kasbon' => '/admin/operational/loans',
            'payroll' => '/admin/payroll',
        ];
    }

    public function test_item_dropdown_tidak_memakai_js_mentah(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        foreach ($this->halamanTabel() as $nama => $url) {
            $html = $this->get($url)->assertOk()->getContent();

            // @js() pada atribut komponen Blade tidak dikompilasi sehingga handler
            // Alpine menerima teks mentah dan modal tidak pernah terbuka.
            $this->assertStringNotContainsString('@js(', $html, "Atribut @js() masih mentah di halaman {$nama}.");
        }
    }

    public function test_setiap_modal_berada_dalam_scope_alpine_pemiliknya(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        foreach ($this->halamanTabel() as $nama => $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertTrue(
                $this->semuaModalDalamScope($html),
                "Ada modal yang berada di luar scope Alpine pada halaman {$nama}."
            );
        }
    }

    public function test_tag_tabel_seimbang(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        foreach ($this->halamanTabel() as $nama => $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertSame(
                preg_match_all('/<table\b/i', $html),
                preg_match_all('/<\/table>/i', $html),
                "Jumlah <table> dan </table> tidak seimbang pada halaman {$nama}."
            );
        }
    }

    /**
     * Telusuri pohon elemen: modal harus memakai scope Alpine yang sama dengan
     * wadah tabelnya, sehingga kunci x-show-nya benar-benar terbaca.
     */
    private function semuaModalDalamScope(string $html): bool
    {
        $dokumen = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dokumen->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dokumen);

        $wadah = $xpath->query('//*[@data-wadah-tabel]')->item(0);
        if (! $wadah instanceof \DOMElement) {
            return true;
        }

        $scopeWadah = $this->scopeAlpineTerdekat($wadah);

        $modal = $xpath->query('//*[@role="dialog"][@aria-labelledby]');
        if ($modal === false) {
            return true;
        }

        foreach ($modal as $simpul) {
            $kunci = trim((string) $simpul->getAttribute('x-show'));
            if ($kunci === '') {
                continue;
            }

            // Modal global (author & lightbox) punya scope sendiri, bukan milik halaman.
            if (in_array($kunci, ['modalAuthorTerbuka', 'terbuka'], true)) {
                continue;
            }

            if ($this->scopeAlpineTerdekat($simpul) !== $scopeWadah) {
                return false;
            }
        }

        return true;
    }

    /**
     * Elemen x-data terdekat (termasuk elemen itu sendiri).
     */
    private function scopeAlpineTerdekat(\DOMNode $simpul): ?\DOMElement
    {
        $kini = $simpul;
        while ($kini instanceof \DOMElement) {
            if ($kini->getAttribute('x-data') !== '') {
                return $kini;
            }
            $kini = $kini->parentNode;
        }

        return null;
    }
}
