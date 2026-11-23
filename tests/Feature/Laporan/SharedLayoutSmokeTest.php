<?php

namespace Tests\Feature\Laporan;

use App\Livewire\Aduan\LessonLearn;
use App\Livewire\Aduan\ReportIndex;
use App\Livewire\AuditTrail\AuditTrailIndex;
use App\Livewire\Category\CategoryIndex;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Dashboard\OfficerDashboard;
use App\Livewire\Dashboard\UserDashboard;
use App\Livewire\Role\RoleIndex;
use App\Livewire\User\UserIndex;
use App\Models\User;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * Final guard for the surfaces this spec shares with the rest of the system
 * (.kiro/specs/laporan-keseluruhan-ui-refresh, task 13.2).
 *
 * Laporan Keseluruhan is one page, but four of the files it changed are
 * shared by every other screen: the sidebar partial
 * (resources/views/layouts/navbar.blade.php), the sidebar CSS blocks and the
 * appended component classes in resources/css/app.css, and the single new
 * prop on resources/views/components/filter-bar.blade.php. This suite renders
 * the pages that consume those files and asserts they are no worse off than
 * before (Requirement 8 criterion 13, Requirement 9 criteria 9 and 10,
 * Requirement 10 criteria 10, 11, 12 and 14).
 *
 * Two things worth stating about how the assertions are framed.
 *
 * The sidebar markup *did* legitimately change here: task 2.1 closed an
 * unbalanced <div> and pointed the Laporan group's open condition at the real
 * `laporan` path prefix, and task 2.2 added the missing `active` conditions on
 * seven submenu items. So byte-equality against the old markup is the wrong
 * check — it would fail for the very edits the spec asked for. What must hold
 * instead are the invariants Requirement 10 criteria 10 to 12 actually state:
 * the same set of menu items, the same label text, the same href values, the
 * same per-role visibility, and (criteria 6 and 7) exactly one active item for
 * any given menu path.
 *
 * The assertions are also scoped rather than exhaustive. This working tree
 * carries unrelated uncommitted work, including an in-progress pergerakan
 * module that adds its own sidebar group and its own routes. A fixed total
 * item count, or a strict equality against the whole observed menu, would
 * fail because of that module and not because of anything this spec did. So
 * the catalogue below lists the entries this spec's requirements name, and
 * the assertions are "every one of these is present, with this href, in this
 * order, for exactly these roles" plus "none of the ones this role may not
 * see is present" — which is what the criteria ask, and which the other
 * module's entries cannot break.
 */
class SharedLayoutSmokeTest extends FeatureTestCase
{
    /** Roles whose sidebar is asserted, including the no-permission baseline. */
    private const ROLES = ['pengguna', 'jtk', 'helpdesk.admin', 'penyelia', 'pengurusan', 'superadmin'];

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user->fresh();
    }

    /**
     * The non-laporan pages that share the sidebar, each paired with a role
     * that holds the permission its route gate requires.
     *
     * /peranan carries no route middleware (the listing is gated in the
     * sidebar by manage.role), so it is requested as the role the menu offers
     * it to.
     *
     * @return array<string, string>  path => role
     */
    private function nonLaporanPages(): array
    {
        return [
            '/dashboard' => 'pengguna',                 // no gate: everyone lands here
            '/dashboard/utama' => 'helpdesk.admin',     // can:access-main-dashboard
            '/aduan' => 'helpdesk.admin',               // can:aduan.all
            '/aduan/untuk-tindakan' => 'jtk',           // can:aduan.updateTask
            '/aduan/lesson-learn' => 'jtk',             // can:aduan.updateTask
            '/kategori' => 'helpdesk.admin',            // can:manage.category
            '/aras-risiko' => 'helpdesk.admin',         // can:manage.category
            '/status' => 'helpdesk.admin',              // can:manage.category
            '/pengguna' => 'penyelia',                  // can:manage.user (bukan helpdesk.admin)
            '/peranan' => 'superadmin',                 // manage.role in the sidebar
            '/audit-trail' => 'superadmin',             // can:manage.audit-trail
        ];
    }

    /**
     * The seven other laporan screens, exactly as routes/web.php registers
     * them. The rating screen is `soal-selidik`, not `mengikut-rating`, and
     * `mengikut-user` is still commented out — see
     * test_the_laporan_paths_that_do_not_exist_are_not_registered.
     *
     * @return list<string>
     */
    private function laporanPages(): array
    {
        return [
            '/laporan',
            '/laporan/mengikut-status-tindakan',
            '/laporan/mengikut-kategori',
            '/laporan/mengikut-tahap-risiko',
            '/laporan/soal-selidik',
            '/laporan/mengikut-bahagian',
            '/laporan/mengikut-peranan',
        ];
    }

    // ------------------------------------------------------------------
    // Pages still render
    // ------------------------------------------------------------------

    /**
     * Every non-laporan page that inherits layouts.app still renders, with
     * the shared shell intact: the sidebar aside, the sidebar nav and the
     * main column the sidebar's width and margin apply to.
     *
     * **Validates: Requirements 9.9, 9.10, 10.14**
     */
    public function test_non_laporan_pages_that_share_the_sidebar_still_render()
    {
        foreach ($this->nonLaporanPages() as $path => $role) {
            $response = $this->actingAs($this->userWithRole($role))->get($path);

            $response->assertOk();

            $html = $response->getContent();

            foreach (['<aside class="app-sidebar">', 'app-sidebar__nav', 'app-main'] as $marker) {
                $this->assertStringContainsString(
                    $marker,
                    $html,
                    "{$path} tidak lagi merender `{$marker}` layout bersama."
                );
            }
        }
    }

    /**
     * The other laporan screens still render. They share the sidebar,
     * x-filter-bar and the stylesheet with Laporan Keseluruhan but were
     * declared out of scope, so a 200 here is the whole point.
     *
     * All seven are asserted the same way, `/laporan/mengikut-bahagian`
     * included: task 18.1 made its aggregate portable, so it now returns 200
     * under the SQLite connection phpunit.xml configures. The per-department
     * counts that query produces are covered by
     * tests/Feature/Laporan/LaporanByDepartmentTest.php (task 18.2); here the
     * only concern is that the shared layout still renders.
     *
     * **Validates: Requirements 9.9, 9.10, 10.14**
     */
    public function test_every_other_laporan_screen_still_renders()
    {
        $admin = $this->userWithRole('helpdesk.admin');

        $paths = $this->laporanPages();
        $this->assertCount(7, $paths);

        foreach ($paths as $path) {
            $this->actingAs($admin)
                ->get($path)
                ->assertOk()
                ->assertSee('app-sidebar__nav', false);
        }
    }

    /**
     * The three paths an earlier draft of this task named do not exist, and
     * must not be asserted against: the rating report lives at
     * `laporan/soal-selidik`, and `laporan/mengikut-user` is commented out in
     * routes/web.php. Locking that here keeps the list above honest.
     *
     * **Validates: Requirements 9.10**
     */
    public function test_the_laporan_paths_that_do_not_exist_are_not_registered()
    {
        $uris = [];
        foreach (app('router')->getRoutes() as $route) {
            $uris[] = $route->uri();
        }

        foreach ([
            'laporan/mengikut-rating',
            'laporan/mengikut-pengguna',
            'laporan/mengikut-user',
        ] as $absent) {
            $this->assertNotContains($absent, $uris);
        }

        // ...and the seven that do exist are registered, so this test cannot
        // pass by the route table being empty.
        foreach ($this->laporanPages() as $path) {
            $this->assertContains(ltrim($path, '/'), $uris);
        }
    }

    // ------------------------------------------------------------------
    // x-filter-bar: the nine other consumer views
    // ------------------------------------------------------------------

    /**
     * The one prop task 8.1 added to x-filter-bar is opt-in, so the nine
     * other views that use the component must render the "Set Semula" button
     * exactly as before: the same four attributes in the same order, and no
     * disabled, wire:loading or wire:target.
     *
     * The opening tag is compared against a pattern rather than searched for
     * keywords, so an attribute nobody thought to blacklist cannot slip in
     * either.
     *
     * Six of the nine only show the button once a filter is set, which is why
     * the table below carries a bit of state.
     *
     * **Validates: Requirements 8.13**
     */
    public function test_the_nine_other_filter_bar_views_render_an_untouched_reset_button()
    {
        $cases = [
            'livewire/dashboard/dashboard' => [Dashboard::class, [], 'helpdesk.admin', []],
            'livewire/dashboard/officer-dashboard' => [OfficerDashboard::class, [], 'jtk', []],
            'livewire/dashboard/user-dashboard' => [UserDashboard::class, [], 'pengguna', []],
            'livewire/aduan/report-index' => [ReportIndex::class, ['scope' => 'all'], 'helpdesk.admin', ['search' => 'x']],
            'livewire/aduan/lesson-learn' => [LessonLearn::class, [], 'jtk', ['search' => 'x']],
            'livewire/audit-trail/audit-trail-index' => [AuditTrailIndex::class, [], 'superadmin', ['search' => 'x']],
            'livewire/category/category-index' => [CategoryIndex::class, [], 'helpdesk.admin', ['search' => 'x']],
            'livewire/role/role-index' => [RoleIndex::class, [], 'superadmin', ['search' => 'x']],
            'livewire/user/user-index' => [UserIndex::class, ['scope' => 'pengguna'], 'helpdesk.admin', ['search' => 'x']],
        ];

        $this->assertCount(9, $cases);

        foreach ($cases as $view => [$component, $params, $role, $state]) {
            $test = Livewire::actingAs($this->userWithRole($role))->test($component, $params);

            foreach ($state as $property => $value) {
                $test->set($property, $value);
            }

            $buttons = $this->resetButtons($test->html());

            $this->assertCount(
                1,
                $buttons,
                "{$view} tidak merender tepat satu butang \"Set Semula\"."
            );

            $this->assertSame(
                1,
                preg_match(
                    '/^<button type="button" class="btn btn-sm btn-outline-secondary" wire:click="[^"]*">$/',
                    $this->openingTag($buttons[0])
                ),
                "Tag butang \"Set Semula\" {$view} tidak lagi sama seperti sebelum prop ditambah: "
                    .$this->openingTag($buttons[0])
            );

            foreach (['disabled', 'wire:loading', 'wire:target'] as $attribute) {
                $this->assertStringNotContainsString(
                    $attribute,
                    $buttons[0],
                    "Butang \"Set Semula\" {$view} kini membawa `{$attribute}`."
                );
            }
        }
    }

    /**
     * The counter-check: Laporan Keseluruhan, the one view that does pass the
     * prop, gets both attributes. Without this the test above would also pass
     * for a prop that never works.
     *
     * **Validates: Requirements 8.13**
     */
    public function test_the_prop_does_take_effect_on_the_view_that_passes_it()
    {
        $html = Livewire::actingAs($this->userWithRole('helpdesk.admin'))
            ->test(\App\Livewire\Laporan\ReportAll::class)
            ->html();

        $buttons = $this->resetButtons($html);
        $this->assertCount(1, $buttons);

        $this->assertStringContainsString('wire:loading.attr="disabled"', $buttons[0]);
        $this->assertStringContainsString('wire:target="', $buttons[0]);
    }

    // ------------------------------------------------------------------
    // Sidebar invariants
    // ------------------------------------------------------------------

    /**
     * For each role, the sidebar carries the same menu entries, label text
     * and href values as before the refresh, in the same order, and nothing
     * the role is not entitled to see.
     *
     * **Validates: Requirements 10.10, 10.11, 10.12**
     */
    public function test_sidebar_items_labels_and_hrefs_are_unchanged_for_every_role()
    {
        foreach (self::ROLES as $role) {
            $user = $this->userWithRole($role);

            $response = $this->actingAs($user)->get('/dashboard');
            $response->assertOk();

            $this->assertSidebarMatchesCatalogue($response->getContent(), $user, $role, '/dashboard');
        }
    }

    /**
     * On every page that shares the sidebar, exactly one menu item carries
     * the `active` class — the one for the page being viewed — and the menu
     * is still the one that role's permissions entitle it to.
     *
     * This is the assertion task 2.2 was aiming at: before it, several
     * submenu items had no active condition at all, so a path could render
     * with zero active items; a sloppy fix could instead light up two
     * (a prefix match and an exact match colliding).
     *
     * **Validates: Requirements 10.10, 10.11, 10.12, 10.14**
     */
    public function test_exactly_one_sidebar_item_is_active_on_every_shared_page()
    {
        $pages = $this->nonLaporanPages();

        foreach ($this->laporanPages() as $path) {
            $pages[$path] = 'helpdesk.admin';
        }

        // The spec's own page is included: its submenu entry is the one task
        // 2.1 fixed the open condition for.
        $pages['/laporan/keseluruhan'] = 'helpdesk.admin';

        foreach ($pages as $path => $role) {
            $user = $this->userWithRole($role);

            $response = $this->actingAs($user)->get($path);
            $response->assertOk();

            $items = $this->sidebarItems($response->getContent());

            $active = array_values(array_filter($items, fn ($item) => $item['active']));

            $this->assertCount(
                1,
                $active,
                "{$path} menandakan ".count($active)." item sidebar sebagai aktif, bukan satu: "
                    .implode(', ', array_column($active, 'label'))
            );

            // ...and the menu itself is still intact on that page.
            $this->assertSidebarMatchesCatalogue($response->getContent(), $user, $role, $path);
        }
    }

    /**
     * The section titles Requirement 10 criterion 9 names are still present,
     * in order, for the role that sees all of them.
     *
     * "Selenggara" is filtered out: it is the pergerakan module's title, not
     * one of this spec's.
     *
     * **Validates: Requirements 10.10, 10.14**
     */
    public function test_sidebar_section_titles_are_unchanged_in_order()
    {
        $response = $this->actingAs($this->userWithRole('superadmin'))->get('/dashboard');
        $response->assertOk();

        preg_match_all(
            '/class="app-sidebar__title"[^>]*>(.*?)</s',
            $this->sidebarHtml($response->getContent()),
            $matches
        );

        $titles = array_values(array_diff(
            array_map('trim', $matches[1]),
            ['Selenggara']
        ));

        $this->assertSame([
            'Laman Utama',
            'Peg Teknikal',
            'Pentadbir Helpdesk',
            'Pentadbiran',
            'Tetapan',
            'Manual',
        ], $titles);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Every "Set Semula" button in a chunk of HTML, whole tag included.
     *
     * @return list<string>
     */
    private function resetButtons(string $html): array
    {
        preg_match_all('/<button\b[^>]*>.*?<\/button>/s', $html, $matches);

        return array_values(array_filter(
            $matches[0],
            fn ($button) => str_contains($button, 'Set Semula')
        ));
    }

    private function openingTag(string $element): string
    {
        preg_match('/^<[a-z]+\b[^>]*>/i', $element, $matches);

        return $matches[0] ?? '';
    }

    /** The sidebar nav only — the topbar has its own markup. */
    private function sidebarHtml(string $html): string
    {
        $start = strpos($html, '<nav class="app-sidebar__nav">');
        $this->assertNotFalse($start, 'Markup sidebar tidak dijumpai dalam respons.');

        $end = strpos($html, '</nav>', $start);
        $this->assertNotFalse($end, 'Elemen <nav> sidebar tidak ditutup.');

        return substr($html, $start, $end - $start);
    }

    /**
     * The sidebar's menu entries in document order.
     *
     * Group triggers (the collapsible parents) have no href and are reported
     * with href null, which is how the catalogue lists them.
     *
     * @return list<array{label: string, href: ?string, active: bool}>
     */
    private function sidebarItems(string $pageHtml): array
    {
        preg_match_all(
            '/<a\s+class="app-nav-link([^"]*)"([^>]*)>(.*?)<\/a>/s',
            $this->sidebarHtml($pageHtml),
            $matches,
            PREG_SET_ORDER
        );

        $items = [];

        foreach ($matches as $match) {
            preg_match('/<span>(.*?)<\/span>/s', $match[3], $label);

            $items[] = [
                'label' => trim($label[1] ?? ''),
                'href' => preg_match('/href="([^"]*)"/', $match[2], $href) ? $href[1] : null,
                'active' => in_array('active', preg_split('/\s+/', trim($match[1])), true),
            ];
        }

        return $items;
    }

    /**
     * Every sidebar entry Requirement 10 names, in document order, with the
     * roles entitled to see it.
     *
     * Derived from the @can / @canany / @hasrole guards in
     * resources/views/layouts/navbar.blade.php against the permission sets in
     * database/seeders/RoleSeeder.php; superadmin sees everything through the
     * Gate::before hook in AuthServiceProvider.
     *
     * The Pergerakan group is deliberately absent — it belongs to the
     * in-progress pergerakan-pegawai spec, and this spec must neither depend
     * on nor forbid it.
     *
     * @return list<array{label: string, href: ?string, roles: list<string>}>
     */
    private function sidebarCatalogue(User $user): array
    {
        $everyone = self::ROLES;
        $officer = ['jtk', 'superadmin'];                                   // aduan.updateTask
        $helpdeskAdmin = ['helpdesk.admin', 'penyelia', 'superadmin'];      // aduan.adminTask / manage.category
        $userManagers = ['penyelia', 'superadmin'];                         // manage.user
        $roleManagers = ['penyelia', 'superadmin'];                         // manage.role
        $management = ['helpdesk.admin', 'penyelia', 'pengurusan', 'superadmin']; // view.report / access-main-dashboard
        $superadminOnly = ['superadmin'];                                   // manage.admin / audit / backup

        $item = fn (string $label, ?string $href, array $roles) => compact('label', 'href', 'roles');

        return [
            $item('Dashboard Pengurusan', route('dashboard.main'), $management),
            $item('Dashboard Saya', url('/dashboard'), $everyone),
            $item('Knowledge Base', route('aduan.lesson-learn'), $officer),

            $item('Aduan', null, $everyone),
            $item('Cipta Aduan', url('/aduan/create'), $everyone),
            $item('Aduan Saya', url('/aduan/user').'/'.$user->username, $everyone),
            $item('Untuk Tindakan', route('aduan.index-untuk-tindakan'), $officer),
            $item('Senarai Aduan', route('aduan.index-selesai'), $officer),
            $item('Aduan Baru', url('/aduan/dihantar'), $helpdeskAdmin),
            $item('Senarai Aduan', url('/aduan'), ['jtk', 'helpdesk.admin', 'penyelia', 'superadmin']),

            $item('Laporan', null, $management),
            $item('Laporan Aduan', url('/laporan/keseluruhan'), $management),

            $item('Selenggaraan', null, $helpdeskAdmin),
            $item('Kategori Aduan', route('category.index'), $helpdeskAdmin),
            $item('Status Aduan', route('status.index'), $helpdeskAdmin),
            $item('Tahap Risiko Aduan', route('risk-level.index'), $helpdeskAdmin),

            $item('Pengurusan Pengguna', null, $userManagers),
            $item('Pentadbir Helpdesk', url('/pengguna/pentadbir'), $superadminOnly),
            $item('Pengurusan', url('/pengguna/pengurusan'), $userManagers),
            $item('Peg. Teknikal', url('/pengguna/peg-teknikal'), $userManagers),
            $item('Pengguna', url('/pengguna'), $userManagers),

            $item('Pengurusan Peranan', url('/peranan'), $roleManagers),
            $item('Tetapan Notifikasi', route('settings.notifications'), $roleManagers),
            $item('Log Sistem', url('/audit-trail'), $superadminOnly),
            $item('Backup', url('/backup'), $superadminOnly),

            $item('Manual Pengguna', null, $everyone),
            $item('Pengguna', asset('manual_pengguna/pengguna.pdf'), $everyone),
            $item('Peg. Teknikal', asset('manual_pengguna/peg-teknikal.pdf'), $officer),
            $item('Admin Helpdesk', asset('manual_pengguna/admin.pdf'), $helpdeskAdmin),
            $item('Superadmin', asset('manual_pengguna/superadmin.pdf'), $superadminOnly),
        ];
    }

    /**
     * Assert the rendered sidebar carries exactly the catalogue entries this
     * role is entitled to — same labels, same hrefs, same order — and none of
     * the entries it is not entitled to.
     *
     * Entries are keyed on label *and* href, because three labels legitimately
     * appear twice with different targets ("Senarai Aduan" for /aduan and
     * /aduan/selesai-saya, "Pengguna" and "Peg. Teknikal" for a user list and
     * a manual PDF). A one-sided "label present" check could not tell those
     * apart, and would pass if two entries swapped hrefs.
     *
     * Observed entries outside the catalogue (the pergerakan module's) are
     * dropped rather than failed, so another spec's menu additions cannot
     * break this; everything in the catalogue is matched positionally, so a
     * missing, renamed, re-pointed, reordered or role-leaked entry does fail.
     */
    private function assertSidebarMatchesCatalogue(string $pageHtml, User $user, string $role, string $path): void
    {
        $key = fn (string $label, ?string $href) => $label.'|'.($href ?? '');

        $expected = [];
        $known = [];

        foreach ($this->sidebarCatalogue($user) as $entry) {
            $k = $key($entry['label'], $entry['href']);
            $known[$k] = true;

            if (in_array($role, $entry['roles'], true)) {
                $expected[] = $k;
            }
        }

        $observed = [];
        foreach ($this->sidebarItems($pageHtml) as $item) {
            $k = $key($item['label'], $item['href']);

            if (isset($known[$k])) {
                $observed[] = $k;
            }
        }

        $this->assertSame(
            $expected,
            $observed,
            "Sidebar {$path} bagi peranan {$role} tidak lagi membawa set item, label dan href yang sama."
        );
    }
}
