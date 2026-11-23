<?php

namespace Tests\Feature\Laporan;

use App\Models\User;
use Tests\FeatureTestCase;

/**
 * Kepala_Halaman Laporan Keseluruhan.
 * Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 6.1 / 6.3)
 *
 * Ujian contoh bagi Requirement 1 kriteria 1, 2, 3 dan 6.
 */
class LaporanHeaderTest extends FeatureTestCase
{
    private function managementUser(): User
    {
        $user = User::factory()->create();
        $user->syncRoles(['helpdesk.admin']);

        return $user;
    }

    public function test_page_title_is_rendered_exactly_once()
    {
        $response = $this->actingAs($this->managementUser())->get('/laporan/keseluruhan');

        $response->assertOk();

        $this->assertSame(
            1,
            substr_count($response->getContent(), 'Laporan Keseluruhan'),
            'Teks "Laporan Keseluruhan" mesti dirender tepat sekali dalam kandungan halaman.'
        );
    }

    public function test_header_renders_subtitle_with_component_class()
    {
        $response = $this->actingAs($this->managementUser())->get('/laporan/keseluruhan');

        $response->assertOk();
        $response->assertSee(
            '<p class="report-head__sub">Ringkasan dan analisis keseluruhan aduan/helpdesk dalam sistem.</p>',
            false
        );
    }

    public function test_breadcrumb_has_three_levels_with_linked_home_and_plain_last_level()
    {
        $response = $this->actingAs($this->managementUser())->get('/laporan/keseluruhan');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertSame(1, preg_match('#<ol class="breadcrumb m-0">(.*?)</ol>#s', $content, $matches));

        $breadcrumb = $matches[1];

        $this->assertSame(
            3,
            substr_count($breadcrumb, '<li class="breadcrumb-item'),
            'Breadcrumb mesti mempunyai tepat tiga aras.'
        );

        // "Laman Utama" ialah pautan ke dashboard.index.
        $this->assertStringContainsString(route('dashboard.index'), $breadcrumb);
        $this->assertStringContainsString('Laman Utama', $breadcrumb);

        // Aras terakhir "Keseluruhan" ialah teks, bukan pautan.
        $this->assertSame(
            1,
            preg_match('#<li class="breadcrumb-item active">\s*Keseluruhan\s*</li>#', $breadcrumb)
        );

        // Susunan tiga aras.
        $this->assertLessThan(strpos($breadcrumb, 'Laporan</li>'), strpos($breadcrumb, 'Laman Utama'));
        $this->assertLessThan(strpos($breadcrumb, 'Keseluruhan'), strpos($breadcrumb, 'Laporan</li>'));
    }
}
